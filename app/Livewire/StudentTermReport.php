<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\GradingScale;
use App\Models\Student;
use App\Models\StudentEnrolment;
use App\Models\Term;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentTermReport extends Component
{
    #[Url(as: 'term')]
    public string $termId = '';

    #[Url(as: 'student')]
    public string $studentId = '';

    public function mount(): void
    {
        $school = Auth::user()->school;
        if (! Term::where('school_id', $school->id)->whereKey($this->termId)->exists()) {
            $this->termId = (string) $school->currentTerm()?->id;
        }
        $this->selectValidStudent();
    }

    public function updatedTermId(): void
    {
        $this->studentId = '';
        $this->selectValidStudent();
    }

    protected function selectValidStudent(): void
    {
        $query = $this->studentsQuery();
        if (! $query->whereKey($this->studentId)->exists()) {
            $this->studentId = (string) ($this->studentsQuery()->value('students.id') ?? '');
        }
    }

    protected function studentsQuery(): Builder
    {
        $schoolId = Auth::user()->school_id;

        return Student::query()
            ->where('students.school_id', $schoolId)
            ->when($this->termId, fn (Builder $query) => $query->where(function (Builder $scope) {
                $scope->whereHas('enrolments', fn (Builder $enrolment) => $enrolment->where('term_id', $this->termId))
                    ->orWhere('students.term_id', $this->termId);
            }))
            ->orderBy('students.name');
    }

    protected function gradePoint(string $grade): float
    {
        return match (strtoupper(trim($grade))) {
            'A+', 'A1' => 4.0,
            'A', 'A2' => 3.75,
            'B+' => 3.5,
            'B', 'B1', 'B2' => 3.0,
            'C+' => 2.5,
            'C', 'C1', 'C2' => 2.0,
            'D+', 'D1' => 1.5,
            'D', 'D2', 'E' => 1.0,
            default => 0.0,
        };
    }

    protected function gradesFor(Student $student, Term $term): Collection
    {
        $scales = GradingScale::where('school_id', $student->school_id)->orderByDesc('minimum_percentage')->get();

        return DB::table('exam_marks')
            ->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')
            ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
            ->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')
            ->where('exam_marks.student_id', $student->id)
            ->where('exams.school_id', $student->school_id)
            ->where('exams.term_id', $term->id)
            ->where('exam_paper_submissions.status', 'approved')
            ->whereNotNull('exam_marks.score')
            ->select([
                'subjects.id as subject_id',
                'subjects.name as subject_name',
                DB::raw('sum(exam_marks.score * exam_papers.weighting) as weighted_score'),
                DB::raw('sum(exam_papers.maximum_score * exam_papers.weighting) as weighted_maximum'),
                DB::raw('avg(exam_papers.weighting) as credit_hours'),
            ])
            ->groupBy('subjects.id', 'subjects.name')
            ->orderBy('subjects.name')
            ->get()
            ->map(function (object $row) use ($scales): object {
                $percentage = (float) $row->weighted_maximum > 0
                    ? round((float) $row->weighted_score / (float) $row->weighted_maximum * 100, 2)
                    : 0;
                $scale = $scales->first(fn (GradingScale $item) => $percentage >= (float) $item->minimum_percentage && $percentage <= (float) $item->maximum_percentage);
                $grade = $scale?->grade ?? '—';

                return (object) [
                    'subject' => (object) ['id' => $row->subject_id, 'name' => $row->subject_name],
                    'subject_name' => $row->subject_name,
                    'credit_hours' => max(1, (float) $row->credit_hours),
                    'percentage' => $percentage,
                    'grade_name' => $grade,
                    'grade_point' => $this->gradePoint($grade),
                    'remarks' => $scale?->remark ?? 'Grade not configured',
                ];
            });
    }

    protected function attendanceFor(Student $student, Term $term): Collection
    {
        $base = AttendanceRecord::where([
            'school_id' => $student->school_id,
            'term_id' => $term->id,
            'student_id' => $student->id,
        ]);
        $daily = (clone $base)->where('session_key', 'daily')->get();

        return $daily->isNotEmpty() ? $daily : $base->whereNotNull('subject_id')->get();
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = Term::where('school_id', $school->id)->find($this->termId);
        $students = $this->studentsQuery()->with('schoolClass')->get();
        $student = $this->studentId !== ''
            ? $this->studentsQuery()->with(['schoolClass', 'stream', 'guardians'])->find($this->studentId)
            : null;
        $grades = collect();
        $attendance = collect();
        $gpa = 0.0;

        if ($student && $term) {
            $enrolment = StudentEnrolment::with(['schoolClass', 'stream'])
                ->where('school_id', $school->id)->where('term_id', $term->id)->where('student_id', $student->id)->first();
            if ($enrolment) {
                $student->setRelation('schoolClass', $enrolment->schoolClass);
                $student->setRelation('stream', $enrolment->stream);
            }
            $student->setAttribute('section', $student->stream?->name ?? '—');
            $student->setAttribute('photo_url', $student->photoUrl());
            $grades = $this->gradesFor($student, $term);
            $attendance = $this->attendanceFor($student, $term);
            $credits = $grades->sum('credit_hours');
            $gpa = $credits > 0 ? round($grades->sum(fn ($grade) => $grade->grade_point * $grade->credit_hours) / $credits, 2) : 0;
        }

        $attendancePresent = $attendance->whereIn('status', ['present', 'late'])->count();
        $attendanceTotal = $attendance->count();
        $average = $grades->avg('percentage');
        $overallScale = $average !== null ? GradingScale::where('school_id', $school->id)->where('minimum_percentage', '<=', $average)->where('maximum_percentage', '>=', $average)->first() : null;
        $teacherRemarks = $overallScale?->remark ?? ($grades->isEmpty() ? 'No approved examination results are available for this term.' : 'Keep working consistently in every subject.');
        $issueDate = $term?->closed_at ?? now();
        $school->setAttribute('logo_url', $school->badge_path ? Storage::disk('public')->url($school->badge_path) : null);
        $report = (object) ['student' => $student, 'term' => $term, 'grades' => $grades, 'attendance_present' => $attendancePresent, 'attendance_total' => $attendanceTotal, 'gpa' => $gpa, 'teacher_remarks' => $teacherRemarks, 'issue_date' => $issueDate];

        return view('livewire.student-term-report', [
            'school' => $school,
            'terms' => Term::where('school_id', $school->id)->orderByDesc('year')->orderByDesc('id')->get(),
            'students' => $students,
            'term' => $term,
            'student' => $student,
            'grades' => $grades,
            'attendance' => $attendance,
            'attendance_present' => $attendancePresent,
            'attendance_total' => $attendanceTotal,
            'gpa' => $gpa,
            'teacher_remarks' => $teacherRemarks,
            'issue_date' => $issueDate,
            'report' => $report,
            'examResults' => collect(),
            'pageTitle' => 'Student Term Report',
        ]);
    }
}
