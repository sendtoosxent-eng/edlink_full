<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Exam;
use App\Models\AttendanceRecord;
use App\Services\TeacherExamReport;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;

class TeacherReportsController extends ApiController
{
    public function exams(Request $request)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && $user->hasPermission('exams.results'), 403);
        $exams = Exam::with('schoolClass:id,name', 'term:id,name,year')->where('school_id', $user->school_id)->latest()->get()
            ->filter(fn ($exam) => TeacherAcademicScope::canViewExam($user, $exam->school_class_id, $exam->term_id));
        return $this->ok($exams->map(fn ($exam) => ['id' => $exam->id, 'name' => $exam->name, 'class_name' => $exam->schoolClass?->name, 'term' => $exam->term?->name, 'year' => $exam->term?->year, 'published' => $exam->published_at !== null])->values());
    }

    public function show(Request $request, int $exam)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && $user->hasPermission('exams.results'), 403);
        $exam = Exam::with('schoolClass', 'papers.subject', 'term')->where('school_id', $user->school_id)->findOrFail($exam);
        abort_unless(TeacherAcademicScope::canViewExam($user, $exam->school_class_id, $exam->term_id), 403);
        $report = app(TeacherExamReport::class)->calculate($exam);
        return $this->ok([
            'name' => $exam->name, 'class_name' => $exam->schoolClass?->name, 'published' => $exam->published_at !== null,
            'readiness' => $report['readiness'],
            'learners' => $report['results']->map(fn ($row) => [
                'id' => $row['student']->id, 'name' => $row['student']->name, 'admission_no' => $row['student']->admission_no,
                'average' => $row['average'], 'grade' => $row['grade'], 'position' => $row['position'],
                'subjects' => collect($row['subjects'])->map(fn ($subject) => ['name' => $subject['paper']->subject?->name, 'score' => $subject['score'], 'maximum' => $subject['paper']->maximum_score, 'grade' => $subject['grade'], 'applicable' => $subject['applicable']])->values(),
            ])->values(),
        ]);
    }

    public function attendance(Request $request)
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && $user->hasPermission('attendance.reports'), 403);
        $data = $request->validate(['from' => ['nullable', 'date'], 'to' => ['nullable', 'date', 'after_or_equal:from'], 'status' => ['nullable', 'in:present,absent,late,excused'], 'search' => ['nullable', 'string', 'max:100']]);
        $term = $user->school->currentTerm();
        $query = TeacherAcademicScope::scopeAttendance(AttendanceRecord::where('school_id', $user->school_id), $user, $term?->id)
            ->when($term, fn ($q) => $q->where('term_id', $term->id))
            ->when($data['from'] ?? null, fn ($q, $date) => $q->whereDate('attendance_date', '>=', $date))
            ->when($data['to'] ?? null, fn ($q, $date) => $q->whereDate('attendance_date', '<=', $date))
            ->when($data['status'] ?? null, fn ($q, $status) => $q->where('status', $status))
            ->when($data['search'] ?? null, fn ($q, $search) => $q->whereHas('student', fn ($s) => $s->where('name', 'like', '%'.$search.'%')));
        $counts = (clone $query)->selectRaw('status, count(*) total')->groupBy('status')->pluck('total', 'status');
        $rows = $query->with('student:id,name,admission_no', 'subject:id,name')->latest('attendance_date')->paginate(40);
        return $this->ok(['counts' => $counts, 'records' => $rows->through(fn ($row) => ['id' => $row->id, 'name' => $row->student?->name, 'date' => $row->attendance_date?->toDateString(), 'subject' => $row->subject?->name ?? 'Daily register', 'status' => $row->status])]);
    }
}
