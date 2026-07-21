<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\SchoolSetting;
use App\Models\StaffLeave;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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

    public function mount(): void
    {
        $this->startsOn = now()->toDateString();
        $this->endsOn = now()->toDateString();
        $this->staffId = (string) Auth::id();
    }

    private function canApprove(): bool
    {
        return in_array(Auth::user()->role, ['admin', 'registrar'], true);
    }

    public function requestLeave(): void
    {
        $schoolId = Auth::user()->school_id;
        $this->validate([
            'staffId' => ['required', 'integer', 'exists:users,id'],
            'type' => ['required', 'string', 'max:100'],
            'startsOn' => ['required', 'date'],
            'endsOn' => ['required', 'date', 'after_or_equal:startsOn'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        $staff = User::where('school_id', $schoolId)->where('employment_status', 'active')->find($this->staffId);
        if (! $staff) {
            $this->addError('staffId', 'Choose an active member of staff from this school.');
            return;
        }

        if (! $this->canApprove() && (int) $this->staffId !== Auth::id()) {
            abort(403);
        }

        try {
            $leave = StaffLeave::create([
                'school_id' => $schoolId,
                'user_id' => $staff->id,
                'type' => $this->type,
                'starts_on' => $this->startsOn,
                'ends_on' => $this->endsOn,
                'reason' => $this->reason ?: null,
                'status' => 'pending',
            ]);
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
        $this->updateStatus($id, 'approved');
    }

    public function reject(int $id): void
    {
        $this->updateStatus($id, 'rejected');
    }

    private function updateStatus(int $id, string $status): void
    {
        abort_unless($this->canApprove(), 403);
        $leave = StaffLeave::where('school_id', Auth::user()->school_id)->findOrFail($id);

        try {
            $leave->update(['status' => $status, 'approved_by' => Auth::id()]);
            AuditLog::record($leave->school_id, 'leave.'.$status, $leave, ['staff' => $leave->staff?->name, 'type' => $leave->type]);
            session()->flash('status', 'Leave request '.$status.'.');
        } catch (Throwable $exception) {
            Log::error('Leave approval update failed.', ['leave_id' => $id, 'exception' => $exception]);
            session()->flash('error', 'The leave request could not be updated. Please try again.');
        }
    }

    public function render()
    {
        $schoolId = Auth::user()->school_id;
        $types = collect(explode(',', (string) SchoolSetting::getValue($schoolId, 'leave_types', 'Annual Leave,Sick Leave,Maternity Leave,Paternity Leave,Study Leave,Unpaid Leave')))->map(fn ($type) => trim($type))->filter();
        $staff = User::where('school_id', $schoolId)->where('employment_status', 'active')->orderBy('name')->get(['id', 'name', 'job_title']);
        $leaves = StaffLeave::with(['staff:id,name,job_title', 'approver:id,name'])->where('school_id', $schoolId)->when($this->statusFilter !== 'all', fn ($query) => $query->where('status', $this->statusFilter))->latest()->get();

        return view('livewire.leaves', compact('types', 'staff', 'leaves'), ['canApprove' => $this->canApprove(), 'pageTitle' => 'Leave Requests']);
    }
}
