<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\SchoolEvent;
use App\Models\SchoolSetting;
use App\Models\Student;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class PortalHome extends Component
{
    public string $selectedStudentId = '';

    public function mount(): void
    {
        $this->selectedStudentId = (string) ($this->linkedStudentIds()->first() ?? '');
    }

    public function updatedSelectedStudentId(): void
    {
        if (! $this->linkedStudentIds()->contains((int) $this->selectedStudentId)) {
            $this->selectedStudentId = (string) ($this->linkedStudentIds()->first() ?? '');
        }
    }

    protected function linkedStudentIds(): Collection
    {
        $user = Auth::user();
        $ids = $user->portalStudents()->where('students.school_id', $user->school_id)->pluck('students.id');

        if ($user->role === 'parent') {
            $ids = $ids->merge(
                Student::where('school_id', $user->school_id)
                    ->whereHas('guardians', fn ($query) => $query->where('email', $user->email))
                    ->pluck('id')
            );
        }

        return $ids->map(fn ($id) => (int) $id)->unique()->values();
    }

    public function render()
    {
        $user = Auth::user();
        abort_unless(in_array($user->role, ['parent', 'student'], true), 403);

        $school = $user->school;
        $term = $school->currentTerm();
        $studentIds = $this->linkedStudentIds();
        $students = Student::with(['schoolClass', 'stream'])->where('school_id', $school->id)->whereIn('id', $studentIds)->orderBy('name')->get();

        if (! $studentIds->contains((int) $this->selectedStudentId)) {
            $this->selectedStudentId = (string) ($students->first()?->id ?? '');
        }

        $student = $students->firstWhere('id', (int) $this->selectedStudentId);
        $graduation = $student?->activeGraduation()->with('term')->first();
        if ($graduation) $term = $graduation->term;
        $studentAttendance = $student ? AttendanceRecord::where('school_id', $school->id)
            ->when($term, fn ($query) => $query->where('term_id', $term->id))
            ->where('student_id', $student->id)->latest('attendance_date')->get() : collect();

        $payments = $student ? DB::table('fee_payments')->where('school_id', $school->id)
            ->when($term, fn ($query) => $query->where('term_id', $term->id))
            ->where('student_id', $student->id)->latest('paid_at')->get() : collect();

        $exams = $student ? Exam::with('term')->where('school_id', $school->id)->whereNotNull('published_at')
            ->when($term, fn ($query) => $query->where('term_id', $term->id))
            ->where('school_class_id', $student->school_class_id)->latest('published_at')->get() : collect();

        $feeRule = SchoolSetting::where(['school_id' => $school->id, 'key' => 'results_fee_clearance_required'])->value('value') === 'enabled';
        $events = SchoolEvent::where('school_id', $school->id)
            ->whereIn('target_audience', ['all', $user->role === 'parent' ? 'parents' : 'students'])
            ->whereDate('event_date', '>=', today())->orderBy('event_date')->take(5)->get();

        $notifications = DB::table('school_notifications')
            ->where('school_id', $school->id)
            ->where(fn ($query) => $query->whereNull('user_id')->orWhere('user_id', $user->id))
            ->latest()->limit(10)->get()
            ->map(fn ($notification) => [
                'type' => 'announcement',
                'message' => $notification->title.': '.$notification->message,
            ]);
        if ($student) {
            if ($user->role === 'parent' && $term && $student->balance($term) > 0) {
                $notifications->push(['type' => 'fees', 'message' => $student->name.' has an outstanding balance of '.number_format($student->balance($term), 0).'.']);
            }
            $latestAttendance = $studentAttendance->first();
            if ($latestAttendance && in_array($latestAttendance->status, ['absent', 'late'], true)) {
                $notifications->push(['type' => 'attendance', 'message' => $student->name.' was marked '.ucfirst($latestAttendance->status).' on '.$latestAttendance->attendance_date->format('d M Y').'.']);
            }
        }
        foreach ($exams as $exam) $notifications->push(['type' => 'results', 'message' => $exam->name.' results have been published.']);
        foreach ($events as $event) $notifications->push(['type' => 'event', 'message' => $event->title.' — '.$event->event_date->format('d M Y').'.']);

        $week = collect(range(6, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
        $attendanceSeries = $week->map(fn ($day) => $studentAttendance->filter(fn ($record) => $record->attendance_date->isSameDay($day))->whereIn('status', ['present', 'late'])->count())->values();
        $absenceSeries = $week->map(fn ($day) => $studentAttendance->filter(fn ($record) => $record->attendance_date->isSameDay($day))->where('status', 'absent')->count())->values();

        $performance = $student && $term ? DB::table('exam_marks')
            ->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')
            ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
            ->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')
            ->where('exam_marks.student_id', $student->id)->where('exams.school_id', $school->id)->where('exams.term_id', $term->id)
            ->where('exam_paper_submissions.status', 'approved')
            ->select('subjects.name', DB::raw('round(avg(exam_marks.score / exam_papers.maximum_score * 100), 1) as average'))
            ->groupBy('subjects.id', 'subjects.name')->orderBy('subjects.name')->get() : collect();

        $timetable = $student && $term ? DB::table('timetable_slots')->leftJoin('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')
            ->where('timetable_slots.school_id', $school->id)->where('timetable_slots.term_id', $term->id)
            ->where('timetable_slots.school_class_id', $student->school_class_id)->where('day_of_week', now()->format('l'))
            ->orderBy('starts_at')->get(['starts_at', 'ends_at', 'label', 'subjects.name as subject']) : collect();

        $fees = [
            'expected' => $student && $term ? $student->totalDue($term) : 0,
            'paid' => $student && $term ? $student->totalPaid($term) : 0,
            'balance' => $student && $term ? max(0, $student->balance($term)) : 0,
        ];
        $isParent = $user->role === 'parent';

        return view('livewire.student-dashboard', compact(
            'school', 'term', 'students', 'student', 'studentAttendance', 'attendanceSeries', 'absenceSeries',
            'performance', 'timetable', 'events', 'exams', 'feeRule', 'notifications', 'payments', 'fees', 'isParent', 'graduation'
        ), ['pageTitle' => $isParent ? 'Parent Dashboard' : 'Student Dashboard']);
    }
}
