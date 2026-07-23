<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\StaffAttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class StaffAttendance extends Component
{
    use WithPagination;

    public string $tab = 'mark';
    public string $attendanceDate = '';
    public string $search = '';
    public string $statusFilter = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public array $statuses = [];
    public array $notes = [];

    public function mount(): void
    {
        $this->attendanceDate = now()->toDateString();
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->loadDay();
    }

    public function updatedAttendanceDate(): void { $this->loadDay(); }
    public function updatedSearch(): void { $this->resetPage(); }
    public function updatedStatusFilter(): void { $this->resetPage(); }
    public function updatedDateFrom(): void { $this->resetPage(); }
    public function updatedDateTo(): void { $this->resetPage(); }

    public function setTab(string $tab): void
    {
        abort_unless(in_array($tab, ['mark', 'history'], true), 422);
        $this->tab = $tab;
        $this->resetPage();
    }

    public function markAll(string $status): void
    {
        abort_unless(in_array($status, StaffAttendanceRecord::STATUSES, true), 422);
        foreach ($this->activeStaff()->pluck('id') as $id) $this->statuses[$id] = $status;
    }

    public function loadDay(): void
    {
        if (! $this->attendanceDate) return;
        $records = StaffAttendanceRecord::where('school_id', Auth::user()->school_id)
            ->whereDate('attendance_date', $this->attendanceDate)->get();
        $this->statuses = $records->pluck('status', 'user_id')->all();
        $this->notes = $records->pluck('note', 'user_id')->map(fn ($note) => $note ?? '')->all();
    }

    public function save(): void
    {
        abort_unless($this->canMark(), 403);
        $this->validate([
            'attendanceDate' => ['required', 'date', 'before_or_equal:today'],
            'statuses.*' => ['required', 'in:'.implode(',', StaffAttendanceRecord::STATUSES)],
            'notes.*' => ['nullable', 'string', 'max:500'],
        ]);

        $staff = $this->activeStaff()->get();
        if ($staff->isEmpty()) {
            session()->flash('error', 'There are no active staff members to mark.');
            return;
        }

        DB::transaction(function () use ($staff) {
            foreach ($staff as $member) {
                StaffAttendanceRecord::updateOrCreate(
                    ['user_id' => $member->id, 'attendance_date' => $this->attendanceDate],
                    ['school_id' => Auth::user()->school_id, 'status' => $this->statuses[$member->id] ?? 'present', 'note' => filled($this->notes[$member->id] ?? null) ? trim($this->notes[$member->id]) : null, 'recorded_by' => Auth::id()]
                );
            }
            AuditLog::record(Auth::user()->school_id, 'staff_attendance.saved', null, ['attendance_date' => $this->attendanceDate, 'staff_count' => $staff->count()]);
        });

        $this->loadDay();
        session()->flash('status', 'Staff attendance saved for '.$staff->count().' staff members.');
    }

    protected function canMark(): bool
    {
        return Auth::user()->hasPermission('staff.attendance');
    }

    protected function activeStaff(): Builder
    {
        return User::where('school_id', Auth::user()->school_id)
            ->where('employment_status', 'active')
            ->whereNotIn('role', ['student', 'parent'])
            ->orderBy('name');
    }

    protected function historyQuery(): Builder
    {
        return StaffAttendanceRecord::with(['staff:id,name,staff_number,job_title', 'recorder:id,name'])
            ->where('school_id', Auth::user()->school_id)
            ->when($this->dateFrom, fn ($query) => $query->whereDate('attendance_date', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($query) => $query->whereDate('attendance_date', '<=', $this->dateTo))
            ->when($this->statusFilter, fn ($query) => $query->where('status', $this->statusFilter))
            ->when($this->search, fn ($query) => $query->whereHas('staff', fn ($staff) => $staff->where(fn ($q) => $q->where('name', 'like', '%'.$this->search.'%')->orWhere('staff_number', 'like', '%'.$this->search.'%'))));
    }

    public function render()
    {
        $staff = $this->activeStaff()->get();
        $records = $this->historyQuery()->latest('attendance_date')->orderBy('user_id')->paginate(40);
        $dayCounts = collect(StaffAttendanceRecord::STATUSES)->mapWithKeys(fn ($status) => [$status => collect($this->statuses)->filter(fn ($value) => $value === $status)->count()]);

        return view('livewire.staff-attendance', [
            'staff' => $staff,
            'records' => $records,
            'statusesList' => StaffAttendanceRecord::STATUSES,
            'dayCounts' => $dayCounts,
            'canMark' => $this->canMark(),
            'pageTitle' => 'Staff Attendance',
        ]);
    }
}