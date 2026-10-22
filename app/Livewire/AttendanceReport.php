<?php

namespace App\Livewire;

use App\Support\CsvSafe;
use App\Support\TeacherAcademicScope;
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
        abort_unless(Auth::user()->hasPermission('attendance.reports'), 403);
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
                fputcsv($out, CsvSafe::row([
                    $record->attendance_date?->format('Y-m-d'),
                    $record->lesson_time ?: '',
                    $record->subject?->name ?? 'Daily register',
                    $record->student?->name,
                    $record->student?->admission_no,
                    $record->schoolClass?->name ?? $record->student?->schoolClass?->name,
                    $record->stream?->name ?? $record->student?->stream?->name,
                    ucfirst($record->status),
                    $record->recorder?->name,
                ]));
            }
            fclose($out);
        }, 'attendance-report-'.$this->fromDate.'-to-'.$this->toDate.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function recordsQuery(): Builder
    {
        abort_unless(Auth::user()->hasPermission('attendance.reports'), 403);
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        $query = AttendanceRecord::with(['student.schoolClass', 'student.stream', 'subject', 'schoolClass', 'stream', 'recorder:id,name'])
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

        return TeacherAcademicScope::scopeAttendance($query, Auth::user(), $term?->id);
    }

    public function exportExcel()
    {
        $summary = $this->attendanceSummary();
        $columns = ['Class'];
        foreach (['Boys', 'Girls'] as $gender) {
            foreach (['Present', 'Late', 'Absent', 'Excused', 'Total'] as $status) {
                $columns[] = $gender.' '.$status;
            }
        }
        $columns = array_merge($columns, ['Unspecified gender', 'Class total']);
        $toValues = function ($row) {
            $values = [$row['class']];
            foreach (['male', 'female'] as $gender) {
                foreach (['present', 'late', 'absent', 'excused', 'total'] as $status) {
                    $values[] = $row['groups'][$gender][$status];
                }
            }
            return array_merge($values, [$row['unspecified'], $row['total']]);
        };
        $rows = $summary->map($toValues)->all();
        $totals = ['Grand total'];
        for ($column = 1; $column < count($columns); $column++) {
            $totals[] = array_sum(array_column($rows, $column));
        }
        $rows[] = $totals;
        $categoryRows = $this->attendanceSummary(true)->map($toValues)->all();
        $rows[] = [];
        $rows[] = ['Attendance by current student category'];
        $rows[] = array_merge(['Category'], array_slice($columns, 1, -1), ['Category total']);
        foreach ($categoryRows as $categoryRow) {
            $rows[] = $categoryRow;
        }
        $categoryTotals = ['Category grand total'];
        for ($column = 1; $column < count($columns); $column++) {
            $categoryTotals[] = array_sum(array_column($categoryRows, $column));
        }
        $rows[] = $categoryTotals;
        $path = app(\App\Services\SimpleXlsxExportService::class)->make([
            'title' => 'Attendance summary', 'school' => Auth::user()->school,
            'from' => $this->fromDate, 'to' => $this->toDate, 'generatedAt' => now(),
            'columns' => $columns, 'rows' => $rows, 'currencyColumns' => [],
        ]);

        return response()->streamDownload(function () use ($path) {
            try {
                readfile($path);
            } finally {
                unlink($path);
            }
        }, 'attendance-summary-'.now()->format('Ymd-His').'.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function summaryData(): array
    {
        $query = $this->recordsQuery();
        return [
            'classes' => $this->attendanceSummary(),
            'categories' => $this->attendanceSummary(true),
            'counts' => (clone $query)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status'),
            'records' => $query->latest('attendance_date')->orderBy('id')->paginate(40)->through(fn ($record) => [
                'id' => $record->id, 'name' => $record->student?->name ?? 'Deleted learner', 'date' => $record->attendance_date?->toDateString(),
                'subject' => $record->subject?->name ?? 'Daily register', 'status' => $record->status,
                'class_name' => $record->schoolClass?->name ?? $record->student?->schoolClass?->name,
            ]),
        ];
    }

    protected function attendanceSummary(bool $byCategory = false): \Illuminate\Support\Collection
    {
        $categories = $byCategory
            ? \App\Models\StudentCategory::where('school_id', Auth::user()->school_id)->orderBy('name')->pluck('name', 'id')
            : collect();
        $grouped = $this->recordsQuery()->get()->groupBy(function (AttendanceRecord $record) use ($byCategory, $categories): string {
            if ($byCategory) {
                $id = $record->student?->student_category_id;
                return $categories->has($id) ? (string) $id : 'unassigned';
            }
            return (string) ($record->school_class_id ?? $record->student?->school_class_id ?? 'unassigned');
        });
        foreach ($categories as $id => $name) {
            if (! $grouped->has($id)) {
                $grouped->put($id, collect());
            }
        }
        return $grouped->map(function ($classRecords, $key) use ($byCategory, $categories): array {
            $first = $classRecords->first();
            $className = $byCategory ? $categories->get($key, 'Unassigned category') : ($first->schoolClass?->name ?? $first->student?->schoolClass?->name ?? 'Unassigned class');
            $groups = collect(['male' => 'Boys', 'female' => 'Girls'])->mapWithKeys(function (string $label, string $gender) use ($classRecords): array {
                $records = $classRecords->filter(fn (AttendanceRecord $record): bool => strtolower(trim((string) $record->student?->gender)) === $gender);

                return [$gender => [
                    'label' => $label,
                    'present' => $records->where('status', 'present')->count(),
                    'late' => $records->where('status', 'late')->count(),
                    'absent' => $records->where('status', 'absent')->count(),
                    'excused' => $records->where('status', 'excused')->count(),
                    'total' => $records->count(),
                ]];
            });

            return ['class' => $className, 'groups' => $groups, 'unspecified' => $classRecords->count() - $groups->sum('total'), 'total' => $classRecords->count()];
        })->sortBy('class')->values();
    }

    public function render()
    {
        $school = Auth::user()->school;
        $user = Auth::user();
        $term = $school->currentTerm();
        $classIds = TeacherAcademicScope::isTeacher($user) ? TeacherAcademicScope::academicClassIds($user, $term?->id) : null;
        $subjectIds = TeacherAcademicScope::isTeacher($user) ? TeacherAcademicScope::subjectAssignments($user, $term?->id)->pluck('subject_id')->unique() : null;
        $base = $this->recordsQuery();
        $counts = (clone $base)->selectRaw('status, count(*) as total')->groupBy('status')->pluck('total', 'status');
        $total = (int) $counts->sum();
        $attended = (int) ($counts['present'] ?? 0) + (int) ($counts['late'] ?? 0);
        $attendanceSummary = $this->attendanceSummary();

        return view('livewire.attendance-report', [
            'records' => $base->latest('attendance_date')->orderBy('lesson_time')->orderBy('id')->paginate(40),
            'counts' => $counts,
            'total' => $total,
            'rate' => $total ? round($attended / $total * 100, 1) : 0,
            'attendanceSummary' => $attendanceSummary,
            'categorySummary' => $this->attendanceSummary(true),
            'classes' => SchoolClass::where('school_id', $school->id)->when($classIds, fn (Builder $query) => $query->whereIn('id', $classIds))->orderBy('name')->get(),
            'streams' => Stream::where('school_id', $school->id)->when($classIds, fn (Builder $query) => $query->whereIn('school_class_id', $classIds))->when($this->schoolClassId, fn (Builder $query) => $query->where('school_class_id', $this->schoolClassId))->orderBy('name')->get(),
            'subjects' => Subject::where('school_id', $school->id)->when($subjectIds, fn (Builder $query) => $query->whereIn('id', $subjectIds))->orderBy('name')->get(),
            'term' => $school->currentTerm(),
            'pageTitle' => 'Attendance Reports',
        ]);
    }
}
