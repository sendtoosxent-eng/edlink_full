<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\SchoolSetting;
use App\Models\StaffLeave;
use App\Models\User;
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
        $termId = Auth::user()->school->currentTerm()?->id;
        $this->handoverSubjectCount = DB::table('staff_subjects')->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)->when($termId, fn ($query) => $query->where(fn ($scope) => $scope->where('term_id', $termId)->orWhereNull('term_id')))->count();
        $this->handoverTimetableCount = DB::table('timetable_slots')->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)->when($termId, fn ($query) => $query->where('term_id', $termId))->count();
        if ($leave->staff?->role === 'teacher' && ($this->handoverSubjectCount + $this->handoverTimetableCount) > 0) {
            $this->approvingLeaveId = $leave->id;
            $this->replacementStaffId = '';
            return;
        }
        $this->finalizeDecision($leave, 'approved');
    }

    public function confirmApproval(): void
    {
        abort_unless($this->canApprove(), 403);
        $this->validate(['replacementStaffId' => ['required', 'integer', 'exists:users,id']]);
        $leave = StaffLeave::with('staff')->where('school_id', Auth::user()->school_id)->where('status', 'pending')->findOrFail($this->approvingLeaveId);
        $replacement = User::where('school_id', $leave->school_id)->where('employment_status', 'active')->whereIn('role', ['teacher', 'admin', 'academic_admin'])->find($this->replacementStaffId);
        if (! $replacement || $replacement->id === $leave->user_id) { $this->addError('replacementStaffId', 'Choose another active teacher or academic administrator from this school.'); return; }
        $termId = Auth::user()->school->currentTerm()?->id;
        $assignments = DB::table('staff_subjects')->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)->when($termId, fn ($query) => $query->where(fn ($scope) => $scope->where('term_id', $termId)->orWhereNull('term_id')))->get();
        $slots = DB::table('timetable_slots')->where('school_id', $leave->school_id)->where('user_id', $leave->user_id)->when($termId, fn ($query) => $query->where('term_id', $termId))->get();
        foreach ($slots as $slot) {
            $conflict = DB::table('timetable_slots')->where('school_id', $leave->school_id)->where('user_id', $replacement->id)->where('term_id', $slot->term_id)->where('day_of_week', $slot->day_of_week)->where('starts_at', $slot->starts_at)->exists();
            if ($conflict) { $this->addError('replacementStaffId', $replacement->name.' already has a timetable lesson on '. $slot->day_of_week.' at '.substr($slot->starts_at, 0, 5).'.'); return; }
        }

        DB::transaction(function () use ($leave, $replacement, $assignments, $slots): void {
            foreach ($assignments as $assignment) {
                DB::table('staff_subjects')->updateOrInsert(['school_id' => $assignment->school_id, 'term_id' => $assignment->term_id, 'user_id' => $replacement->id, 'subject_id' => $assignment->subject_id, 'school_class_id' => $assignment->school_class_id], ['updated_at' => now(), 'created_at' => now()]);
            }
            DB::table('staff_subjects')->whereIn('id', $assignments->pluck('id'))->delete();
            DB::table('timetable_slots')->whereIn('id', $slots->pluck('id'))->update(['user_id' => $replacement->id, 'updated_at' => now()]);
            DB::table('staff_leave_handovers')->insert(['school_id' => $leave->school_id, 'staff_leave_id' => $leave->id, 'from_user_id' => $leave->user_id, 'to_user_id' => $replacement->id, 'subject_assignments' => json_encode($assignments), 'timetable_slots' => json_encode($slots), 'assigned_by' => Auth::id(), 'created_at' => now(), 'updated_at' => now()]);
            $leave->update(['status' => 'approved', 'approved_by' => Auth::id()]);
        });
        AuditLog::record($leave->school_id, 'leave.approved', $leave, ['staff' => $leave->staff?->name, 'replacement' => $replacement->name, 'subjects' => $assignments->count(), 'timetable_slots' => $slots->count()]);
        $this->notifyDecision($leave->fresh('staff'), 'approved', $replacement->name);
        $this->cancelApproval();
        session()->flash('status', 'Leave approved and all current teaching work assigned to '. $replacement->name.'.');
    }

    public function cancelApproval(): void
    {
        $this->reset(['approvingLeaveId', 'replacementStaffId', 'handoverSubjectCount', 'handoverTimetableCount']);
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
        $leaves = StaffLeave::with(['staff:id,name,job_title,role', 'approver:id,name'])->where('school_id', $schoolId)->when(! $this->canApprove(), fn ($query) => $query->where('user_id', Auth::id()))->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))->latest()->get();
        $replacementStaff = User::where('school_id', $schoolId)->where('employment_status', 'active')->whereIn('role', ['teacher', 'admin', 'academic_admin'])->when($this->approvingLeaveId, fn ($query) => $query->where('id', '!=', StaffLeave::whereKey($this->approvingLeaveId)->value('user_id')))->orderBy('name')->get(['id', 'name', 'job_title']);
        return view('livewire.leaves', compact('types', 'staff', 'leaves', 'replacementStaff'), ['canApprove' => $this->canApprove(), 'pageTitle' => $this->canApprove() ? 'Leave Requests' : 'My Leave']);
    }
}
