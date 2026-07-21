<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class AttendanceReport extends Component
{
    use WithPagination;

    public string $fromDate = '';
    public string $toDate = '';
    public string $schoolClassId = '';
    public string $streamId = '';
    public string $subjectId = 'all';
    public string $status = 'all';
    public string $search = '';

    public function mount(): void
    {
        abort_unless(in_array(Auth::user()->role, ['admin', 'academic_admin'], true), 403);
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
    }

    public function updatedSchoolClassId(): void
    {
        $this->streamId = '';
        $this->resetPage();
    }

    public function updated($property): void
    {
        if ($property !== 'schoolClassId') {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset(['schoolClassId', 'streamId', 'search']);
        $this->subjectId = 'all';
        $this->status = 'all';
        $this->fromDate = now()->startOfMonth()->toDateString();
        $this->toDate = now()->toDateString();
        $this->resetPage();
    }

    public function exportCsv()
    {
        $records = $this->recordsQuery()->orderBy('attendance_date')->orderBy('lesson_time')->get();

        return response()->streamDownload(function () use ($records): void {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Date', 'Time', 'Subject/session', 'Learner', 'Admission no.', 'Class', 'Stream', 'Status', 'Recorded by']);
            foreach ($records as $record) {
                fputcsv($out, [
                    $record->attendance_date?->format('Y-m-d'),
                    $record->lesson_time ?: '',
                    $record->subject?->name ?? 'Daily register',
                    $record->student?->name,
                    $record->student?->admission_no,
                    $record->schoolClass?->name ?? $record->student?->schoolClass?->name,
                    $record->stream?->name ?? $record->student?->stream?->name,
                    ucfirst($record->status),
                    $record->recorder?->name,
                ]);
            }
            fclose($out);
        }, 'attendance-report-'.$this->fromDate.'-to-'.$this->toDate.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function recordsQuery(): Builder
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        return AttendanceRecord::with(['student.schoolClass', 'student.stream', 'subject', 'schoolClass', 'stream', 'recorder:id,name'])
            ->where('school_id', $school->id)
            ->when($term, fn (Builder $query) => $query->where('term_id', $term->id))
            ->when($this->fromDate, fn (Builder $query) => $query->whereDate('attendance_date', '>=', $this->fromDate))
            ->when($this->toDate, fn (Builder $query) => $query->whereDate('attendance_date', '<=', $this->toDate))
            ->when($this->schoolClassId, fn (Builder $query) => $query->where(function (Builder $scope) {
                $scope->where('school_class_id', $this->schoolClassId)
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('school_class_id')->whereHas('student', fn (Builder $student) => $student->where('school_class_id', $this->schoolClassId)));
            }))
            ->when($this->streamId, fn (Builder $query) => $query->where(function (Builder $scope) {
                $scope->where('stream_id', $this->streamId)
                    ->orWhere(fn (Builder $legacy) => $legacy->whereNull('stream_id')->whereHas('student', fn (Builder $student) => $student->where('stream_id', $this->streamId)));
            }))
            ->when($this->subjectId === 'daily', fn (Builder $query) => $query->whereNull('subject_id'))
            ->when(! in_array($this->subjectId, ['all', 'daily'], true), fn (Builder $query) => $query->where('subject_id', $this->subjectId))
            ->when($this->status !== 'all', fn (Builder $query) => $query->where('status', $this->status))
            ->when($this->search, fn (Builder $query) => $query->whereHas('student', fn (Builder $student) => $student
                ->where('name', 'like', '%'.$this->search.'%')
                ->orWhere('admission_no', 'like', '%'.$this->search.'%')));
    }

    public function render()
    {
        $school = Auth::user()->school;
        $base = $this->recordsQuery();
        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $attended = (int) ($counts['present'] ?? 0) + (int) ($counts['late'] ?? 0);

        return view('livewire.attendance-report', [
            'records' => $base->latest('attendance_date')->orderBy('lesson_time')->orderBy('id')->paginate(40),
            'counts' => $counts,
            'total' => $total,
            'rate' => $total ? round($attended / $total * 100, 1) : 0,
            'classes' => SchoolClass::where('school_id', $school->id)->orderBy('name')->get(),
            'streams' => Stream::where('school_id', $school->id)->when($this->schoolClassId, fn (Builder $query) => $query->where('school_class_id', $this->schoolClassId))->orderBy('name')->get(),
            'subjects' => Subject::where('school_id', $school->id)->orderBy('name')->get(),
            'term' => $school->currentTerm(),
            'pageTitle' => 'Attendance Reports',
        ]);
    }
}
