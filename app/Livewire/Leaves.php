<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\SchoolSetting;
use App\Models\StaffLeave;
use App\Models\User;
use App\Services\LeaveCoverService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Throwable;

#[Layout('layouts.app')]
class Leaves extends Component
{
    public string $staffId = '';
    public string $type = 'Annual Leave';
    public string $startsOn = '';
    public string $endsOn = '';
    public string $reason = '';
    public string $statusFilter = 'all';
    public ?int $approvingLeaveId = null;
    public string $replacementStaffId = '';
    public int $handoverClassCount = 0;
    public int $handoverSubjectCount = 0;
    public int $handoverTimetableCount = 0;

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'superadmin', 'academic_admin', 'registrar', 'bursar', 'teacher'], true), 403);
        $this->startsOn = now()->toDateString();
        $this->endsOn = now()->toDateString();
        $this->staffId = (string) Auth::id();
    }

    private function canApprove(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'superadmin'], true) || Auth::user()->hasPermission('staff.leaves');
    }

    public function requestLeave(): void
    {
        $schoolId = Auth::user()->school_id;
        if (! $this->canApprove()) $this->staffId = (string) Auth::id();
        $this->validate([
            'staffId' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', 'max:100'],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['required', 'date', 'after_or_equal:startsOn'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $staff = User::where('school_id', $schoolId)->where('employment_status', 'active')->find($this->staffId);
        if (! $staff) { $this->addError('staffId', 'Choose an active member of staff from this school.'); return; }
        if (! $this->canApprove() && $staff->id !== Auth::id()) abort(403);
        $overlap = StaffLeave::where('school_id', $schoolId)->where('user_id', $staff->id)->whereIn('status', ['pending', 'approved'])
            ->whereDate('starts_on', '<=', $this->endsOn)->whereDate('ends_on', '>=', $this->startsOn)->exists();
        if ($overlap) { $this->addError('startsOn', 'This staff member already has a pending or approved leave covering those dates.'); return; }

        try {
            $leave = StaffLeave::create(['school_id' => $schoolId, 'user_id' => $staff->id, 'type' => $this->type, 'starts_on' => $this->startsOn, 'ends_on' => $this->endsOn, 'reason' => $this->reason ?: null, 'status' => 'pending']);
            AuditLog::record($schoolId, 'leave.requested', $leave, ['staff' => $staff->name, 'type' => $leave->type]);
            $this->reset('reason');
            session()->flash('status', 'Leave request submitted for approval.');
        } catch (Throwable $exception) {
            Log::error('Leave request creation failed.', ['school_id' => $schoolId, 'exception' => $exception]);
            $this->addError('request', 'The leave request was not saved. Please try again.');
        }
    }

    public function approve(int $id): void
    {
        abort_unless($this->canApprove(), 403);
        $leave = StaffLeave::with('staff')->where('school_id', Auth::user()->school_id)->where('status', 'pending')->findOrFail($id);
        $duties = app(LeaveCoverService::class)->duties($leave);
        $this->handoverClassCount = $duties['classes']->count();
        $this->handoverSubjectCount = $duties['subjects']->count();
        $this->handoverTimetableCount = $duties['slots']->count();
        if ($this->handoverClassCount + $this->handoverSubjectCount + $this->handoverTimetableCount > 0) {
            $this->approvingLeaveId = $leave->id;
            $this->replacementStaffId = '';
            return;
        }
        app(LeaveCoverService::class)->approve($leave, null, Auth::user());
        $this->notifyDecision($leave->fresh('staff'), 'approved');
        session()->flash('status', 'Leave approved. Availability will follow the approved dates.');
    }

    public function changeCover(int $id): void
    {
        abort_unless($this->canApprove(), 403);
        $leave = StaffLeave::where('school_id', Auth::user()->school_id)->where('status', 'approved')->whereNull('returned_at')->whereDate('ends_on', '>=', today())->whereHas('handover', fn ($q) => $q->where('temporary', true))->findOrFail($id);
        $duties = app(LeaveCoverService::class)->duties($leave);
        $this->approvingLeaveId = $leave->id;
        $this->replacementStaffId = '';
        $this->handoverClassCount = $duties['classes']->count();
        $this->handoverSubjectCount = $duties['subjects']->count();
        $this->handoverTimetableCount = $duties['slots']->count();
    }

    public function confirmApproval(): void
    {
        abort_unless($this->canApprove(), 403);
        $this->validate(['replacementStaffId' => ['required', 'integer']]);
        $leave = StaffLeave::with('staff')->where('school_id', Auth::user()->school_id)->whereIn('status', ['pending', 'approved'])->findOrFail($this->approvingLeaveId);
        app(LeaveCoverService::class)->approve($leave, (int) $this->replacementStaffId, Auth::user(), $leave->status === 'approved');
        $replacement = User::findOrFail($this->replacementStaffId);
        $this->notifyDecision($leave->fresh('staff'), 'approved', $replacement->name);
        $this->cancelApproval();
        session()->flash('status', 'Leave approved. '.$replacement->name.' will cover class and subject duties only during the approved leave dates.');
    }

    public function markReturned(int $id): void
    {
        abort_unless($this->canApprove(), 403);
        $leave = StaffLeave::where('school_id', Auth::user()->school_id)->where('status', 'approved')->whereNull('returned_at')->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today())->findOrFail($id);
        $leave->update(['returned_at' => now(), 'returned_by' => Auth::id()]);
        AuditLog::record($leave->school_id, 'leave.returned', $leave);
        session()->flash('status', 'Return recorded. The original teacher is available and temporary cover has ended.');
    }

    public function cancelApproval(): void
    {
        $this->reset(['approvingLeaveId', 'replacementStaffId', 'handoverClassCount', 'handoverSubjectCount', 'handoverTimetableCount']);
        $this->resetValidation();
    }

    public function reject(int $id): void
    {
        abort_unless($this->canApprove(), 403);
        $leave = StaffLeave::where('school_id', Auth::user()->school_id)->where('status', 'pending')->findOrFail($id);
        $this->finalizeDecision($leave, 'rejected');
    }

    private function finalizeDecision(StaffLeave $leave, string $status): void
    {
        try {
            $leave->update(['status' => $status, 'approved_by' => Auth::id()]);
            AuditLog::record($leave->school_id, 'leave.'.$status, $leave, ['staff' => $leave->staff?->name, 'type' => $leave->type]);
            $this->notifyDecision($leave->fresh('staff'), $status);
            session()->flash('status', 'Leave request '. $status.'.');
        } catch (Throwable $exception) {
            Log::error('Leave decision failed.', ['leave_id' => $leave->id, 'exception' => $exception]);
            session()->flash('error', 'The leave request could not be updated. Please try again.');
        }
    }

    private function notifyDecision(StaffLeave $leave, string $status, ?string $replacement = null): void
    {
        $approved = $status === 'approved';
        $message = 'Your '. $leave->type.' request for '. $leave->starts_on->format('d M Y').' to '. $leave->ends_on->format('d M Y').' was '. $status.'.';
        if ($replacement) $message .= ' Your teaching responsibilities were assigned to '. $replacement.'.';
        DB::table('school_notifications')->insert(['school_id' => $leave->school_id, 'user_id' => $leave->user_id, 'title' => $approved ? 'Leave approved' : 'Leave request rejected', 'message' => $message, 'type' => $approved ? 'success' : 'warning', 'created_at' => now(), 'updated_at' => now()]);
        if ($leave->staff?->email) {
            try {
                $school = Auth::user()->school;
                Mail::raw("Dear {$leave->staff->name},\n\n{$message}\n\nRegards,\n{$school->name}", fn ($mail) => $mail->to($leave->staff->email)->subject($school->name.' - '.($approved ? 'Leave approved' : 'Leave request update')));
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Leave decision email failed.', ['leave_id' => $leave->id, 'email' => $leave->staff->email, 'error' => $exception->getMessage()]);
            }
        }
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;
        $storedTypes = (string) SchoolSetting::getValue($schoolId, 'leave_types', 'standard');
        $types = match ($storedTypes) {
            'basic' => collect(['Annual Leave', 'Sick Leave']),
            'standard' => collect(['Annual Leave', 'Sick Leave', 'Maternity Leave', 'Paternity Leave', 'Compassionate Leave']),
            'extended' => collect(['Annual Leave', 'Sick Leave', 'Maternity Leave', 'Paternity Leave', 'Compassionate Leave', 'Study Leave', 'Unpaid Leave']),
            default => collect(explode(',', $storedTypes))->map(fn ($type) => trim($type))->filter(),
        };
        $staff = $this->canApprove() ? User::where('school_id', $schoolId)->where('employment_status', 'active')->orderBy('name')->get(['id', 'name', 'job_title']) : User::whereKey(Auth::id())->get(['id', 'name', 'job_title']);
        $leaves = StaffLeave::with(['staff:id,name,job_title,role', 'approver:id,name', 'handover.toUser'])->where('school_id', $schoolId)->when(! $this->canApprove(), fn ($query) => $query->where('user_id', Auth::id()))->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))->latest()->get();
        $replacementStaff = User::where('school_id', $schoolId)->where('employment_status', 'active')->whereIn('role', ['teacher', 'admin', 'academic_admin'])->when($this->approvingLeaveId, fn ($query) => $query->where('id', '!=', StaffLeave::whereKey($this->approvingLeaveId)->value('user_id')))->orderBy('name')->get();
        $approvalLeave = $this->approvingLeaveId ? StaffLeave::where('school_id', $schoolId)->find($this->approvingLeaveId) : null;
        $replacementStaff->each(fn ($member) => $member->setAttribute('cover_available', $approvalLeave ? $member->availableBetween($approvalLeave->starts_on, $approvalLeave->ends_on) : ! $member->isOnLeave()));
        return view('livewire.leaves', compact('types', 'staff', 'leaves', 'replacementStaff'), ['canApprove' => $this->canApprove(), 'pageTitle' => $this->canApprove() ? 'Leave Requests' : 'My Leave']);
    }
}
