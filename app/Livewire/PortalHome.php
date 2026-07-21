<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\SchoolEvent;
use App\Models\SchoolSetting;
use App\Models\Student;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.student-dashboard')]
class PortalHome extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['parent', 'student'], true), 403);
        $school = $user->school;
        $term = $school->currentTerm();
        $studentIds = $user->portalStudents()->where('students.school_id', $school->id)->pluck('students.id');

        if ($user->role === 'parent') {
            $studentIds = $studentIds->merge(Student::where('school_id', $school->id)->whereHas('guardians', fn ($query) => $query->where('email', $user->email))->pluck('id'))->unique();
        }

        $students = Student::with(['schoolClass', 'stream'])->where('school_id', $school->id)->whereIn('id', $studentIds)->orderBy('name')->get();
        $studentIds = $students->pluck('id');
        $attendance = AttendanceRecord::where('school_id', $school->id)->when($term, fn ($query) => $query->where('term_id', $term->id))->whereIn('student_id', $studentIds)->latest('attendance_date')->get()->groupBy('student_id');
        $payments = DB::table('fee_payments')->where('school_id', $school->id)->when($term, fn ($query) => $query->where('term_id', $term->id))->whereIn('student_id', $studentIds)->latest('paid_at')->get()->groupBy('student_id');
        $exams = Exam::with('term')->where('school_id', $school->id)->whereNotNull('published_at')->when($term, fn ($query) => $query->where('term_id', $term->id))->whereIn('school_class_id', $students->pluck('school_class_id')->unique())->latest('published_at')->get();
        $feeRule = SchoolSetting::where(['school_id' => $school->id, 'key' => 'results_fee_clearance_required'])->value('value') === 'enabled';
        $events = SchoolEvent::where('school_id', $school->id)->whereIn('target_audience', ['all', $user->role === 'parent' ? 'parents' : 'students'])->whereDate('event_date', '>=', today())->orderBy('event_date')->take(5)->get();

        $notifications = collect();
        foreach ($students as $student) {
            if ($user->role === 'parent' && $term && $student->balance($term) > 0) $notifications->push(['type' => 'arrears', 'message' => $student->name.' has an outstanding balance of '.number_format($student->balance($term), 0).'.']);
            $latestAttendance = $attendance->get($student->id, collect())->first();
            if ($latestAttendance && in_array($latestAttendance->status, ['absent', 'late'], true)) $notifications->push(['type' => 'attendance', 'message' => $student->name.' was marked '.ucfirst($latestAttendance->status).' on '.$latestAttendance->attendance_date->format('d M Y').'.']);
        }
        foreach ($exams as $exam) $notifications->push(['type' => 'results', 'message' => $exam->name.' results have been published.']);
        foreach ($events as $event) $notifications->push(['type' => 'event', 'message' => $event->title.' — '.$event->event_date->format('d M Y').'.']);

        if ($user->role === 'student') {
            $student = $students->first();
            $week = collect(range(6, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
            $studentAttendance = $student ? $attendance->get($student->id, collect()) : collect();
            $attendanceSeries = $week->map(fn ($day) => $studentAttendance->filter(fn ($record) => $record->attendance_date->isSameDay($day))->whereIn('status', ['present', 'late'])->count())->values();
            $absenceSeries = $week->map(fn ($day) => $studentAttendance->filter(fn ($record) => $record->attendance_date->isSameDay($day))->where('status', 'absent')->count())->values();
            $performance = $student && $term ? DB::table('exam_marks')->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')->join('exams', 'exams.id', '=', 'exam_papers.exam_id')->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')->where('exam_marks.student_id', $student->id)->where('exams.school_id', $school->id)->where('exams.term_id', $term->id)->where('exam_paper_submissions.status', 'approved')->select('subjects.name', DB::raw('round(avg(exam_marks.score / exam_papers.maximum_score * 100), 1) as average'))->groupBy('subjects.id', 'subjects.name')->orderBy('subjects.name')->get() : collect();
            $timetable = $student && $term ? DB::table('timetable_slots')->leftJoin('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')->where('timetable_slots.school_id', $school->id)->where('timetable_slots.term_id', $term->id)->where('timetable_slots.school_class_id', $student->school_class_id)->where('day_of_week', now()->format('l'))->orderBy('starts_at')->get(['starts_at', 'ends_at', 'label', 'subjects.name as subject']) : collect();

            return view('livewire.student-dashboard', compact('school', 'term', 'student', 'studentAttendance', 'attendanceSeries', 'absenceSeries', 'performance', 'timetable', 'events', 'exams', 'feeRule', 'notifications'), ['pageTitle' => 'Student Dashboard'])->layout('layouts.student-dashboard');
        }

        return view('livewire.portal-home', compact('school', 'term', 'students', 'attendance', 'payments', 'exams', 'feeRule', 'events', 'notifications'), ['pageTitle' => 'Parent Portal'])->layout('layouts.portal');
    }
}
