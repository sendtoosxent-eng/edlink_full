<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Attendance extends Component
{
    public string $activeTab = 'mark';
    public string $attendanceDate = '';
    public string $schoolClassId = '';
    public string $streamId = '';
    public string $reportFrom = '';
    public string $reportTo = '';
    public array $statuses = [];

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.daily'), 403);
        $this->attendanceDate = now()->toDateString();
        $this->reportFrom = now()->startOfMonth()->toDateString();
        $this->reportTo = now()->toDateString();
    }

    public function updatedAttendanceDate(): void { $this->loadStatuses(); }
    public function updatedSchoolClassId(): void { $this->streamId = ''; $this->loadStatuses(); }
    public function updatedStreamId(): void { $this->loadStatuses(); }

    public function loadStatuses(): void
    {
        $term = Auth::user()->school->currentTerm();
        if (! $term) return;
        $this->statuses = AttendanceRecord::where('school_id', Auth::user()->school_id)->where('term_id', $term->id)->where('session_key', 'daily')->whereDate('attendance_date', $this->attendanceDate)->pluck('status', 'student_id')->all();
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.daily'), 403);
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen()) { session()->flash('error', 'Attendance can only be recorded in an open term.'); return; }
        $this->validate(['attendanceDate' => ['required', 'date'], 'statuses.*' => ['nullable', 'in:present,absent,late,excused']]);
        $students = $this->studentsQuery()->get();
        DB::transaction(function () use ($students, $school, $term) {
            foreach ($students as $student) AttendanceRecord::updateOrCreate(['student_id' => $student->id, 'attendance_date' => $this->attendanceDate, 'session_key' => 'daily'], ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $student->school_class_id, 'stream_id' => $student->stream_id, 'status' => $this->statuses[$student->id] ?? 'present', 'recorded_by' => Auth::id()]);
        });
        session()->flash('status', 'Attendance saved for '.$students->count().' active learners.');
    }

    public function exportPerformance()
    {
        $rows = $this->performanceRows();
        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w'); fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Learner', 'Admission no.', 'Class', 'Recorded days', 'Present / Late', 'Absent', 'Excused', 'Attendance rate']);
            foreach ($rows as $row) fputcsv($out, [$row['name'], $row['admission_no'], $row['class'], $row['total'], $row['present'], $row['absent'], $row['excused'], $row['rate'].'%']);
            fclose($out);
        }, 'attendance-performance-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function studentsQuery()
    {
        return Student::with(['schoolClass', 'stream'])->where('school_id', Auth::user()->school_id)->where('status', 'active')->when($this->schoolClassId, fn ($query) => $query->where('school_class_id', $this->schoolClassId))->when($this->streamId, fn ($query) => $query->where('stream_id', $this->streamId))->orderBy('name');
    }

    private function performanceRows()
    {
        $school = Auth::user()->school; $term = $school->currentTerm();
        $records = AttendanceRecord::where('school_id', $school->id)->where('session_key', 'daily')->when($term, fn ($query) => $query->where('term_id', $term->id))->when($this->reportFrom, fn ($query) => $query->whereDate('attendance_date', '>=', $this->reportFrom))->when($this->reportTo, fn ($query) => $query->whereDate('attendance_date', '<=', $this->reportTo))->get()->groupBy('student_id');
        return $this->studentsQuery()->get()->map(function ($student) use ($records) {
            $items = $records->get($student->id, collect()); $total = $items->count(); $present = $items->whereIn('status', ['present', 'late'])->count();
            return ['name' => $student->name, 'admission_no' => $student->admission_no, 'class' => $student->schoolClass?->name ?? '—', 'total' => $total, 'present' => $present, 'absent' => $items->where('status', 'absent')->count(), 'late' => $items->where('status', 'late')->count(), 'excused' => $items->where('status', 'excused')->count(), 'rate' => $total ? round($present / $total * 100, 1) : 0];
        })->sortBy('rate')->values();
    }

    public function render()
    {
        $school = Auth::user()->school; $term = $school->currentTerm(); $students = $this->studentsQuery()->get();
        $selectedDay = AttendanceRecord::where('school_id', $school->id)->where('session_key', 'daily')->when($term, fn ($query) => $query->where('term_id', $term->id))->whereDate('attendance_date', $this->attendanceDate)->when($this->schoolClassId, fn ($query) => $query->whereHas('student', fn ($q) => $q->where('school_class_id', $this->schoolClassId)))->when($this->streamId, fn ($query) => $query->whereHas('student', fn ($q) => $q->where('stream_id', $this->streamId)))->get();
        $performance = $this->performanceRows();
        return view('livewire.attendance', ['term' => $term, 'classes' => SchoolClass::where('school_id', $school->id)->orderBy('name')->get(), 'streams' => Stream::where('school_id', $school->id)->when($this->schoolClassId, fn ($query) => $query->where('school_class_id', $this->schoolClassId))->orderBy('name')->get(), 'students' => $students, 'selectedDay' => $selectedDay, 'performance' => $performance, 'pageTitle' => 'Attendance']);
    }
}
