<?php

namespace App\Http\Controllers\Api\V1;

use App\Models\Exam;
use App\Models\AttendanceRecord;
use App\Services\TeacherExamReport;
use App\Support\TeacherAcademicScope;
use Illuminate\Http\Request;

class TeacherReportsController extends ApiController
{
    public function attendanceSummary(Request $request)
    {
        $component = $this->attendanceComponent($request);
        $user = $request->user();
        $termId = $user->school->currentTerm()?->id;
        $classIds = TeacherAcademicScope::academicClassIds($user, $termId);
        $subjectIds = TeacherAcademicScope::subjectAssignments($user, $termId)->pluck('subject_id')->unique();
        return $this->ok($component->summaryData() + ['options' => [
            'classes' => \App\Models\SchoolClass::where('school_id', $user->school_id)->whereIn('id', $classIds)->orderBy('name')->get(['id', 'name']),
            'streams' => \App\Models\Stream::where('school_id', $user->school_id)->whereIn('school_class_id', $classIds)->orderBy('name')->get(['id', 'name', 'school_class_id']),
            'subjects' => \App\Models\Subject::where('school_id', $user->school_id)->whereIn('id', $subjectIds)->orderBy('name')->get(['id', 'name']),
        ]]);
    }

    public function attendanceExcel(Request $request)
    {
        return $this->attendanceComponent($request)->exportExcel();
    }

    private function attendanceComponent(Request $request): \App\Livewire\AttendanceReport
    {
        $user = $request->user();
        abort_unless(TeacherAcademicScope::isTeacher($user) && $user->hasPermission('attendance.reports'), 403);
        $filters = $request->validate([
            'from' => ['required', 'date'], 'to' => ['required', 'date', 'after_or_equal:from'],
            'school_class_id' => ['nullable', 'integer', 'min:1'], 'stream_id' => ['nullable', 'integer', 'min:1'],
            'subject' => ['nullable', 'regex:/^(all|daily|[1-9][0-9]*)$/'],
            'status' => ['nullable', 'in:all,present,absent,late,excused'], 'search' => ['nullable', 'string', 'max:100'],
        ]);
        $component = \Livewire\Livewire::new(\App\Livewire\AttendanceReport::class);
        $component->fromDate = $filters['from'];
        $component->toDate = $filters['to'];
        $component->schoolClassId = (string) ($filters['school_class_id'] ?? '');
        $component->streamId = (string) ($filters['stream_id'] ?? '');
        $component->subjectId = (string) ($filters['subject'] ?? 'daily');
        $component->status = $filters['status'] ?? 'all';
        $component->search = $filters['search'] ?? '';
        return $component;
    }

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
