<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\GradingScale;
use App\Models\Student;
use App\Models\StudentEnrolment;
use App\Models\Term;
use App\Services\TermReportCalculator;
use App\Support\TeacherAcademicScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StudentTermReport extends Component
{
    public string $termId = '';
    public string $studentId = '';
    public string $examId = '';

    public function mount(?Student $student = null, ?Exam $exam = null): void
    {
        if ($student && $exam) {
            abort_unless($this->canAccessStudent($student)
                && $exam->school_id === Auth::user()->school_id
                && (! TeacherAcademicScope::isTeacher(Auth::user()) || TeacherAcademicScope::canViewExam(Auth::user(), $exam->school_class_id, $exam->term_id))
                && $exam->school_class_id === $student->school_class_id
                && (! $exam->stream_id || $exam->stream_id === $student->stream_id)
                && (! $this->isPortalUser() || $exam->isPublished()), 404);
            $this->termId = (string) $exam->term_id;
            $this->studentId = (string) $student->id;
            $this->examId = (string) $exam->id;
            return;
        }

        $this->termId = (string) Auth::user()->school->currentTerm()?->id;
        $this->chooseStudentAndExam();
    }

    protected function isPortalUser(): bool
    {
        return in_array(Auth::user()->role, ['parent', 'student'], true);
    }

    protected function linkedStudentIds(): Collection
    {
        $user = Auth::user();
        $ids = $user->portalStudents()->where('students.school_id', $user->school_id)->pluck('students.id');
        if ($user->role === 'parent') {
            $ids = $ids->merge(Student::where('school_id', $user->school_id)->whereHas('guardians', fn ($query) => $query->where('email', $user->email))->pluck('id'));
        }
        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }

    protected function canAccessStudent(Student $student): bool
    {
        if ($student->school_id !== Auth::user()->school_id) {
            return false;
        }
        if ($this->isPortalUser()) {
            return $this->linkedStudentIds()->contains($student->id);
        }
        if (TeacherAcademicScope::isTeacher(Auth::user())) {
            $classIds = TeacherAcademicScope::academicClassIds(Auth::user(), (int) ($this->termId ?: 0));

            return $classIds->contains($student->school_class_id)
                || $student->enrolments()->whereIn('school_class_id', $classIds)->exists();
        }

        return true;
    }

    public function updatedTermId(): void
    {
        $this->studentId = '';
        $this->examId = '';
        $this->chooseStudentAndExam();
    }

    public function updatedStudentId(): void
    {
        $this->examId = '';
        $this->chooseExam();
    }

    protected function chooseStudentAndExam(): void
    {
        $this->studentId = (string) ($this->studentsQuery()->value('students.id') ?? '');
        $this->chooseExam();
    }

    protected function chooseExam(): void
    {
        $this->examId = (string) ($this->examsQuery()->value('exams.id') ?? '');
    }

    protected function studentsQuery(): Builder
    {
        $query = Student::where('students.school_id', Auth::user()->school_id)
            ->when($this->isPortalUser(), fn (Builder $query) => $query->whereIn('students.id', $this->linkedStudentIds()))
            ->when($this->termId, fn (Builder $query) => $query->where(function (Builder $scope) {
                $scope->whereHas('enrolments', fn (Builder $enrolment) => $enrolment->where('term_id', $this->termId))
                    ->orWhere('students.term_id', $this->termId);
            }));

        return TeacherAcademicScope::scopeStudents($query, Auth::user(), (int) ($this->termId ?: 0))
            ->orderBy('students.name');
    }

    protected function examsQuery(): Builder
    {
        $query = Exam::where('school_id', Auth::user()->school_id)->where('term_id', $this->termId ?: 0);
        if ($this->isPortalUser()) $query->whereNotNull('published_at');
        $student = $this->studentId !== '' ? $this->studentsQuery()->find($this->studentId) : null;
        if (! $student) {
            return $query->whereRaw('1 = 0');
        }

        $enrolment = StudentEnrolment::where('school_id', $student->school_id)->where('term_id', $this->termId)->where('student_id', $student->id)->first();
        $classId = $enrolment?->school_class_id ?? $student->school_class_id;
        $streamId = $enrolment?->stream_id ?? $student->stream_id;

        return $query->where('school_class_id', $classId)
            ->where(fn (Builder $scope) => $scope->whereNull('stream_id')->when($streamId, fn (Builder $stream) => $stream->orWhere('stream_id', $streamId)))
            ->orderByDesc('created_at');
    }

    protected function gradePoint(string $grade): float
    {
        return match (strtoupper(trim($grade))) {
            'A+', 'A1' => 4.0, 'A', 'A2' => 3.75, 'B+' => 3.5,
            'B', 'B1', 'B2' => 3.0, 'C+' => 2.5, 'C', 'C1', 'C2' => 2.0,
            'D+', 'D1' => 1.5, 'D', 'D2', 'E' => 1.0, default => 0.0,
        };
    }

    protected function gradesFor(Student $student, Exam $exam): Collection
    {
        $scales = GradingScale::where('school_id', $student->school_id)->where('education_stage', $exam->schoolClass->education_stage)->orderByDesc('minimum_percentage')->get();

        $query = DB::table('exam_marks')->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
            ->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')
            ->where('exam_marks.student_id', $student->id)->where('exam_papers.exam_id', $exam->id)
            ->where('exam_paper_submissions.status', 'approved')->whereNotNull('exam_marks.score')
            ->select('subjects.id as subject_id', 'subjects.name as subject_name', 'exam_marks.score', 'exam_papers.maximum_score', 'exam_papers.weighting');
        $user = Auth::user();
        if (TeacherAcademicScope::isTeacher($user) && ! TeacherAcademicScope::classIds($user)->contains($exam->school_class_id)) {
            $subjectIds = TeacherAcademicScope::subjectAssignments($user, $exam->term_id)
                ->where('school_class_id', $exam->school_class_id)->pluck('subject_id');
            $query->whereIn('subjects.id', $subjectIds);
        }

        return $query->orderBy('subjects.name')->get()->map(function (object $row) use ($scales): object {
                $percentage = (float) $row->maximum_score > 0 ? round((float) $row->score / (float) $row->maximum_score * 100, 2) : 0;
                $scale = $scales->first(fn ($item) => $percentage >= (float) $item->minimum_percentage && $percentage <= (float) $item->maximum_percentage);
                $grade = $scale?->grade ?? '—';
                return (object) ['subject' => (object) ['id' => $row->subject_id, 'name' => $row->subject_name], 'subject_name' => $row->subject_name, 'credit_hours' => max(1, (float) $row->weighting), 'percentage' => $percentage, 'grade_name' => $grade, 'grade_point' => $this->gradePoint($grade), 'aggregate_points' => $scale?->aggregate_points, 'remarks' => $scale?->remark ?? 'Grade not configured'];
            });
    }

    protected function attendanceFor(Student $student, Term $term): Collection
    {
        $base = AttendanceRecord::where(['school_id' => $student->school_id, 'term_id' => $term->id, 'student_id' => $student->id]);
        $user = Auth::user();
        if (TeacherAcademicScope::isTeacher($user)) {
            if (TeacherAcademicScope::classIds($user)->contains($student->school_class_id)) {
                return $base->whereNull('subject_id')->get();
            }
            $subjectIds = TeacherAcademicScope::subjectAssignments($user, $term->id)
                ->where('school_class_id', $student->school_class_id)->pluck('subject_id');

            return $base->whereIn('subject_id', $subjectIds)->get();
        }
        $daily = (clone $base)->where('session_key', 'daily')->get();
        return $daily->isNotEmpty() ? $daily : $base->whereNotNull('subject_id')->get();
    }

    protected function positionFor(Student $student, Exam $exam): ?int
    {
        $rows = DB::table('exam_marks')->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
            ->join('students', 'students.id', '=', 'exam_marks.student_id')
            ->leftJoin('student_enrolments', function ($join) use ($exam) {
                $join->on('student_enrolments.student_id', '=', 'students.id')->where('student_enrolments.term_id', $exam->term_id);
            })
            ->where('exam_papers.exam_id', $exam->id)->where('exam_paper_submissions.status', 'approved')
            ->where('students.school_id', $exam->school_id)
            ->whereRaw('coalesce(student_enrolments.school_class_id, students.school_class_id) = ?', [$exam->school_class_id])
            ->when($exam->stream_id, fn ($query) => $query->whereRaw('coalesce(student_enrolments.stream_id, students.stream_id) = ?', [$exam->stream_id]))
            ->select('students.id', DB::raw('sum(exam_marks.score * exam_papers.weighting) / nullif(sum(exam_papers.maximum_score * exam_papers.weighting), 0) * 100 as average'))
            ->groupBy('students.id')->orderByDesc('average')->get();
        $previous = null; $position = 0;
        foreach ($rows as $index => $row) {
            if ($previous === null || abs((float) $row->average - $previous) >= 0.001) $position = $index + 1;
            if ((int) $row->id === $student->id) return $position;
            $previous = (float) $row->average;
        }
        return null;
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = Term::where('school_id', $school->id)->find($this->termId);
        $students = $this->studentsQuery()->with('schoolClass')->get();
        $exams = $this->examsQuery()->with(['schoolClass', 'stream'])->get();
        $student = $this->studentId !== '' ? $this->studentsQuery()->with(['schoolClass', 'stream', 'guardians'])->find($this->studentId) : null;
        $exam = $this->examId !== '' ? $this->examsQuery()->with(['schoolClass', 'stream'])->find($this->examId) : null;
        $settings = \App\Services\StageReportSettings::get($school->id, \App\Services\SchoolAcademicSetup::stagesFor($school)[0]);
        $grades = collect(); $calculationGrades = collect(); $attendance = collect(); $gpa = 0.0; $aggregate = 0; $position = null; $fees = ['due' => 0, 'paid' => 0, 'balance' => 0]; $promotion = null;

        if ($student && $term && $exam) {
            if ($this->isPortalUser()) {
                $feeRule = \App\Models\SchoolSetting::where(['school_id' => $school->id, 'key' => 'results_fee_clearance_required'])->value('value') === 'enabled';
                abort_if($feeRule && $student->balance($term) > 0, 403, 'Fee clearance is required before viewing this report.');
            }
            $enrolment = StudentEnrolment::with(['schoolClass', 'stream'])->where('school_id', $school->id)->where('term_id', $term->id)->where('student_id', $student->id)->first();
            if ($enrolment) { $student->setRelation('schoolClass', $enrolment->schoolClass); $student->setRelation('stream', $enrolment->stream); }
            $settings = \App\Services\StageReportSettings::get($school->id, $student->schoolClass->education_stage);
            if (TeacherAcademicScope::isTeacher(Auth::user())) {
                $settings['show_fees'] = false;
            }
            $student->setAttribute('section', $student->stream?->name ?? '—');
            $student->setAttribute('photo_url', $student->photoUrl());
            $grades = $this->gradesFor($student, $exam);
            $calculationGrades = $grades->sortByDesc('percentage')->take($settings['best']);
            $credits = $calculationGrades->sum('credit_hours');
            $gpa = $credits > 0 ? round($calculationGrades->sum(fn ($grade) => $grade->grade_point * $grade->credit_hours) / $credits, 2) : 0;
            $aggregate = $calculationGrades->sum(fn ($grade) => (int) ($grade->aggregate_points ?? 0));
            $attendance = $this->attendanceFor($student, $term);
            $canViewWholeClass = ! TeacherAcademicScope::isTeacher(Auth::user()) || TeacherAcademicScope::classIds(Auth::user())->contains($exam->school_class_id);
            $position = $settings['show_position'] && $canViewWholeClass ? $this->positionFor($student, $exam) : null;
            if (! TeacherAcademicScope::isTeacher(Auth::user())) {
                $fees = ['due' => $student->totalDue($term), 'paid' => $student->totalPaid($term), 'balance' => $student->balance($term)];
            }
            $promotion = $enrolment?->promotion_outcome;
        }

        $attendancePresent = $attendance->whereIn('status', ['present', 'late'])->count(); $attendanceTotal = $attendance->count();
        $average = $calculationGrades->avg('percentage');
        $gradingScales = GradingScale::where('school_id', $school->id)->where('education_stage', $settings['stage'])->orderByDesc('minimum_percentage')->get();
        $overallScale = $average !== null ? $gradingScales->first(fn ($scale) => $average >= (float) $scale->minimum_percentage && $average <= (float) $scale->maximum_percentage) : null;
        $teacherRemarks = $grades->isEmpty() ? 'No approved results are available for this examination.' : trim(($overallScale?->remark ?? 'Keep working consistently in every subject.').' Overall result: '.($average >= $settings['pass'] ? 'Pass.' : 'Below the configured pass mark.'));
        $issueDate = $term?->closed_at ?? now();
        $school->setAttribute('logo_url', $school->badge_path ? Storage::disk('public')->url($school->badge_path) : null);
        $report = (object) ['student' => $student, 'term' => $term, 'grades' => $grades, 'attendance_present' => $attendancePresent, 'attendance_total' => $attendanceTotal, 'gpa' => $gpa, 'teacher_remarks' => $teacherRemarks, 'issue_date' => $issueDate];

        return view('livewire.student-term-report', compact('school', 'term', 'students', 'student', 'exams', 'exam', 'settings', 'gradingScales', 'grades', 'attendance', 'gpa', 'aggregate', 'average', 'position', 'fees', 'promotion', 'report') + ['terms' => Term::where('school_id', $school->id)->orderByDesc('year')->orderByDesc('id')->get(), 'attendance_present' => $attendancePresent, 'attendance_total' => $attendanceTotal, 'teacher_remarks' => $teacherRemarks, 'issue_date' => $issueDate, 'pageTitle' => 'Student Term Report']);
    }
}
