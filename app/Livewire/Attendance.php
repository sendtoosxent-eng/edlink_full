<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\SchoolClass;
use App\Models\Stream;
use App\Models\Student;
use App\Services\StudentActivityNotifier;
use App\Support\CsvSafe;
use App\Support\TeacherAcademicScope;
use Illuminate\Support\Carbon;
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

    public array $notes = [];

    public bool $showStudentImages = false;

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.daily'), 403);
        $this->attendanceDate = now()->toDateString();
        $this->reportFrom = now()->startOfMonth()->toDateString();
        $this->reportTo = now()->toDateString();
    }

    public function updatedAttendanceDate(): void
    {
        $this->loadStatuses();
    }

    public function updatedSchoolClassId(): void
    {
        $this->streamId = '';
        $this->loadStatuses();
    }

    public function updatedStreamId(): void
    {
        $this->loadStatuses();
    }

    public function loadStatuses(): void
    {
        $term = Auth::user()->school->currentTerm();
        if (! $term) {
            return;
        }
        $studentIds = $this->studentsQuery()->pluck('id');
        $records = AttendanceRecord::where('school_id', Auth::user()->school_id)
            ->where('term_id', $term->id)
            ->where('session_key', 'daily')
            ->whereDate('attendance_date', $this->attendanceDate)
            ->whereIn('student_id', $studentIds)
            ->get(['student_id', 'status', 'note']);
        $this->statuses = $records->pluck('status', 'student_id')->all();
        $this->notes = $records->mapWithKeys(fn ($record) => [
            $record->student_id => $record->status === 'late'
                ? preg_replace('/^Late by\s+/i', '', (string) $record->note, 1)
                : ($record->note ?? ''),
        ])->all();
    }

    public function save(): void
    {
        abort_unless(Auth::user()->hasPermission('attendance.daily'), 403);
        if ($this->schoolClassId && TeacherAcademicScope::isTeacher(Auth::user())) {
            abort_unless(TeacherAcademicScope::classIds(Auth::user())->contains((int) $this->schoolClassId), 403);
        }
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen()) {
            session()->flash('error', 'Attendance can only be recorded in an open term.');

            return;
        }
        $rules = ['attendanceDate' => ['required', 'date']];
        foreach ($this->studentsQuery()->pluck('id') as $studentId) {
            $status = $this->statuses[$studentId] ?? 'present';
            $rules['statuses.'.$studentId] = ['required', 'in:present,absent,late,excused'];
            $rules['notes.'.$studentId] = $status === 'present'
                ? ['nullable', 'string', 'max:500']
                : ($status === 'late' ? ['required', 'integer', 'min:1', 'max:1440'] : ['required', 'string', 'max:500']);
        }
        $this->validate($rules, ['notes.*.required' => 'Add a reason or the number of minutes late before saving.']);
        $students = $this->studentsQuery()->get();
        $attendanceDate = Carbon::parse($this->attendanceDate)->startOfDay();
        DB::transaction(function () use ($students, $school, $term, $attendanceDate) {
            foreach ($students as $student) {
                $status = $this->statuses[$student->id] ?? 'present';
                $note = $this->notes[$student->id] ?? null;
                $record = AttendanceRecord::updateOrCreate(['student_id' => $student->id, 'attendance_date' => $attendanceDate, 'session_key' => 'daily'], ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $student->school_class_id, 'stream_id' => $student->stream_id, 'status' => $status, 'note' => $status === 'late' ? 'Late by '.$note.' minutes' : ($note ?: null), 'recorded_by' => Auth::id()]);
                app(StudentActivityNotifier::class)->attendance($record);
            }
        });
        session()->flash('status', 'Attendance saved for '.$students->count().' active learners.');
    }

    public function exportPerformance()
    {
        $rows = $this->performanceRows();

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, ['Learner', 'Admission no.', 'Class', 'Recorded days', 'Present / Late', 'Absent', 'Excused', 'Attendance rate']);
            foreach ($rows as $row) {
                fputcsv($out, CsvSafe::row([$row['name'], $row['admission_no'], $row['class'], $row['total'], $row['present'], $row['absent'], $row['excused'], $row['rate'].'%']));
            }
            fclose($out);
        }, 'attendance-performance-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function studentsQuery()
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();

        return Student::with(['schoolClass', 'stream'])
            ->where('school_id', $user->school_id)
            ->where('status', 'active')
            ->when($term, fn ($query) => $query->whereHas('enrolments', fn ($enrolments) => $enrolments->where('term_id', $term->id)->whereIn('status', ['active', 'pending'])))
            ->when(
                TeacherAcademicScope::isTeacher($user),
                fn ($query) => $term
                    ? $query->whereHas('enrolments', fn ($enrolments) => $enrolments->where('term_id', $term->id)->whereIn('school_class_id', TeacherAcademicScope::classIds($user)))
                    : $query->whereIn('school_class_id', TeacherAcademicScope::classIds($user)),
            )
            ->when($this->schoolClassId, fn ($query) => $term
                ? $query->whereHas('enrolments', fn ($enrolments) => $enrolments->where('term_id', $term->id)->where('school_class_id', $this->schoolClassId))
                : $query->where('school_class_id', $this->schoolClassId))
            ->when($this->streamId, fn ($query) => $term
                ? $query->whereHas('enrolments', fn ($enrolments) => $enrolments->where('term_id', $term->id)->where('stream_id', $this->streamId))
                : $query->where('stream_id', $this->streamId))
            ->orderBy('name');
    }

    public function performanceRows()
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $records = AttendanceRecord::where('school_id', $school->id)->where('session_key', 'daily')->when($term, fn ($query) => $query->where('term_id', $term->id))->when($this->reportFrom, fn ($query) => $query->whereDate('attendance_date', '>=', $this->reportFrom))->when($this->reportTo, fn ($query) => $query->whereDate('attendance_date', '<=', $this->reportTo))->get()->groupBy('student_id');

        return $this->studentsQuery()->get()->map(function ($student) use ($records) {
            $items = $records->get($student->id, collect());
            $total = $items->count();
            $present = $items->whereIn('status', ['present', 'late'])->count();

            return ['name' => $student->name, 'photo_url' => $student->photoUrl(), 'admission_no' => $student->admission_no, 'class' => $student->schoolClass?->name ?? '—', 'total' => $total, 'present' => $present, 'absent' => $items->where('status', 'absent')->count(), 'late' => $items->where('status', 'late')->count(), 'excused' => $items->where('status', 'excused')->count(), 'rate' => $total ? round($present / $total * 100, 1) : 0];
        })->sortBy('rate')->values();
    }

    public function render()
    {
        $user = Auth::user();
        $school = $user->school;
        $term = $school->currentTerm();
        $students = $this->studentsQuery()->get();
        $selectedDay = AttendanceRecord::where('school_id', $school->id)->where('session_key', 'daily')->when($term, fn ($query) => $query->where('term_id', $term->id))->whereDate('attendance_date', $this->attendanceDate)->whereIn('student_id', $students->pluck('id'))->get();
        $performance = $this->performanceRows();
        $classes = SchoolClass::where('school_id', $school->id)
            ->when(TeacherAcademicScope::isTeacher($user), fn ($query) => $query->whereIn('id', TeacherAcademicScope::classIds($user)))
            ->orderBy('name')->get();

        return view('livewire.attendance', ['term' => $term, 'classes' => $classes, 'streams' => Stream::where('school_id', $school->id)->when($this->schoolClassId, fn ($query) => $query->where('school_class_id', $this->schoolClassId))->orderBy('name')->get(), 'students' => $students, 'selectedDay' => $selectedDay, 'performance' => $performance, 'pageTitle' => 'Attendance']);
    }
}
