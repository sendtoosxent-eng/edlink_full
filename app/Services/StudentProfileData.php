<?php

namespace App\Services;

use App\Models\AttendanceRecord;
use App\Models\FeePayment;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\User;
use App\Support\TeacherAcademicScope;
use Illuminate\Support\Facades\DB;

class StudentProfileData
{
    public function build(Student $student, User $viewer, ?int $termId = null): array
    {
        $student->load(['school', 'schoolClass', 'stream', 'category', 'guardians', 'term']);
        $terms = $student->school->terms()->orderByDesc('year')->orderByDesc('term_number')->get();
        $term = $termId ? $terms->firstWhere('id', $termId) : ($terms->firstWhere('is_current', true) ?? $student->term ?? $terms->first());
        abort_if($termId && ! $term, 404);
        $enrolments = $student->enrolments()->where('school_id', $student->school_id)
            ->with(['term', 'schoolClass', 'stream', 'category'])->latest('enrolled_at')->get();
        $enrolment = $term ? $enrolments->firstWhere('term_id', $term->id) : null;
        $placementClass = $enrolment?->schoolClass ?? $student->schoolClass;
        $placementStream = $enrolment ? $enrolment->stream : $student->stream;
        $placementCategory = $enrolment?->category ?? $student->category;
        $canEdit = $viewer->hasPermission('students.manage');
        $teacher = TeacherAcademicScope::isTeacher($viewer) && ! $canEdit;
        $classTeacher = TeacherAcademicScope::classIds($viewer)->contains($placementClass?->id);
        $subjectIds = $teacher && ! $classTeacher
            ? TeacherAcademicScope::subjectAssignments($viewer, $term?->id)->where('school_class_id', $placementClass?->id)->pluck('subject_id')
            : null;
        $canViewFinance = $viewer->hasPermission('finance.payments') || $viewer->hasPermission('finance.reports') || $viewer->hasPermission('finance.ledger');
        $currency = (string) SchoolSetting::getValue($student->school_id, 'currency', 'UGX');

        $marks = $term ? DB::table('exam_marks')->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')
            ->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')
            ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
            ->where('exams.school_id', $student->school_id)->where('exams.term_id', $term->id)
            ->where('exam_marks.student_id', $student->id)->whereNotNull('exam_marks.score')
            ->where('exam_paper_submissions.status', 'approved')
            ->when($subjectIds !== null, fn ($query) => $query->whereIn('subjects.id', $subjectIds))
            ->orderByDesc('exams.created_at')->orderBy('subjects.name')
            ->get(['exams.id as exam_id', 'exams.name as exam_name', 'exams.published_at', 'subjects.id as subject_id', 'subjects.name as subject_name', 'exam_marks.score', 'exam_papers.maximum_score'])
            ->map(function ($mark) {
                $mark->percentage = (float) $mark->maximum_score > 0 ? round((float) $mark->score / (float) $mark->maximum_score * 100, 1) : null;
                return $mark;
            }) : collect();
        $examProgress = $marks->groupBy('exam_id')->map(fn ($rows) => [
            'exam' => $rows->first()->exam_name, 'exam_id' => $rows->first()->exam_id,
            'published' => (bool) $rows->first()->published_at, 'subjects' => $rows->count(),
            'average' => $rows->whereNotNull('percentage')->avg('percentage'),
        ])->values();
        $selections = $term ? $student->subjectSelections()->where('term_id', $term->id)->get()->keyBy('subject_id') : collect();
        $subjects = $term && $placementClass ? DB::table('class_subjects')->join('subjects', 'subjects.id', '=', 'class_subjects.subject_id')
            ->where('class_subjects.school_id', $student->school_id)->where('class_subjects.term_id', $term->id)
            ->where('class_subjects.school_class_id', $placementClass->id)
            ->when(StudentSubjectSelectionService::classUsesIndividualSelection($placementClass) && $selections->isNotEmpty(), fn ($query) => $query->whereIn('subjects.id', $selections->keys()))
            ->when($subjectIds !== null, fn ($query) => $query->whereIn('subjects.id', $subjectIds))
            ->orderBy('subjects.name')->get(['subjects.id', 'subjects.name', 'subjects.code'])
            ->map(function ($subject) use ($selections, $marks) {
                $subject->selection_type = $selections->get($subject->id)?->selection_type;
                $subject->average = $marks->where('subject_id', $subject->id)->whereNotNull('percentage')->avg('percentage');
                $subject->assessments = $marks->where('subject_id', $subject->id)->count();
                return $subject;
            }) : collect();

        $attendanceQuery = AttendanceRecord::where('school_id', $student->school_id)->where('student_id', $student->id)->where('term_id', $term?->id ?? 0);
        if ($teacher) {
            TeacherAcademicScope::scopeAttendance($attendanceQuery, $viewer, $term?->id);
        }
        $attendanceCounts = (clone $attendanceQuery)->whereNull('subject_id')->selectRaw('status, COUNT(*) as total')->groupBy('status')->pluck('total', 'status');
        $attendanceTotal = (int) $attendanceCounts->sum();
        $attendanceRate = $attendanceTotal ? round((($attendanceCounts['present'] ?? 0) + ($attendanceCounts['late'] ?? 0)) / $attendanceTotal * 100, 1) : null;
        $attendance = $attendanceQuery->with('subject')->latest('attendance_date')->latest('id')->limit(30)->get();

        $finance = $canViewFinance && $term ? [
            'mapped' => $student->mappedFeeAmount($term), 'due' => $student->totalDue($term),
            'paid' => $student->totalPaid($term), 'balance' => $student->balance($term),
            'arrears' => $student->arrearsDueIn($term), 'adjustments' => $student->feeAdjustmentTotal($term),
        ] : null;
        $payments = $finance ? FeePayment::posted()->where('school_id', $student->school_id)->where('student_id', $student->id)
            ->where('term_id', $term->id)->with('recordedBy')->latest('paid_at')->limit(20)->get() : collect();
        $guardianEmails = $student->guardians->pluck('email')->filter();
        $parentAccounts = User::where('school_id', $student->school_id)->where('role', 'parent')
            ->where(fn ($query) => $query->whereHas('portalStudents', fn ($linked) => $linked->where('students.id', $student->id))
                ->orWhereIn('email', $guardianEmails))->orderBy('name')->get();
        $houses = DB::table('student_house_memberships')->join('student_houses', 'student_houses.id', '=', 'student_house_memberships.student_house_id')
            ->where('student_house_memberships.school_id', $student->school_id)->where('student_id', $student->id)->pluck('student_houses.name');
        $clubs = DB::table('student_club_memberships')->join('student_clubs', 'student_clubs.id', '=', 'student_club_memberships.student_club_id')
            ->where('student_club_memberships.school_id', $student->school_id)->where('student_id', $student->id)->pluck('student_clubs.name');

        return compact('student', 'terms', 'term', 'enrolments', 'placementClass', 'placementStream', 'placementCategory',
            'canEdit', 'canViewFinance', 'currency', 'finance', 'payments', 'marks', 'examProgress', 'subjects',
            'attendance', 'attendanceCounts', 'attendanceTotal', 'attendanceRate', 'parentAccounts', 'houses', 'clubs');
    }
}
