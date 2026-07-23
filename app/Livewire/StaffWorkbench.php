<?php

namespace App\Livewire;

use App\Models\Arrears;
use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\SchoolEvent;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class StaffWorkbench extends Component
{
    public function render()
    {
        $user = Auth::user();
        abort_if(in_array($user->role, ['parent', 'student'], true), 403);

        $school = $user->school;
        $term = $school->currentTerm();
        $designationName = $user->designation?->name ?? '';
        $isFinanceWorkspace = $user->role === 'bursar' || strcasecmp($designationName, 'Bursar') === 0;
        $isTeacherWorkspace = ! $isFinanceWorkspace && ($user->role === 'teacher' || str_contains(strtolower($designationName), 'teacher'));

        $moduleDefinitions = [
            'students' => ['Students & admissions', 'students.index'],
            'finance' => ['Finance', 'fee-payments.index'],
            'attendance' => ['Attendance', $user->hasPermission('attendance.daily') ? 'attendance.index' : 'attendance.subject'],
            'academics' => ['Academics', 'subjects.index'],
            'exams' => ['Exams & results', $user->hasPermission('exams.marks') ? 'exams.marks' : 'exams.results'],
            'staff' => ['Staff', $user->hasPermission('staff.payroll') ? 'payroll.index' : 'staff.index'],
            'parents' => ['Parents & communication', 'parents.index'],
            'reports' => ['Reports', 'reports.index'],
            'settings' => ['Settings', 'settings.index'],
        ];
        $visibleModules = collect($moduleDefinitions)->filter(fn ($definition, $module) => $user->hasModuleAccess($module));

        $finance = [];
        if ($isFinanceWorkspace) {
            $expectedFees = $term ? (float) $school->students()->where('status', 'active')->get()->sum(fn ($student) => $student->mappedFeeAmount($term) ?? 0) : 0;
            $arrears = $term ? (float) Arrears::where('school_id', $school->id)->where('applied_term_id', $term->id)->sum('amount') : 0;
            $income = $term ? (float) FeePayment::where('school_id', $school->id)->where('term_id', $term->id)->sum('amount') : 0;
            $expenses = $term ? (float) Expense::where('school_id', $school->id)->where('term_id', $term->id)->sum('amount') : 0;
            $credits = (float) CashPoolEntry::where('school_id', $school->id)->when($term, fn ($q) => $q->where('term_id', $term->id))->where('direction', 'credit')->sum('amount');
            $debits = (float) CashPoolEntry::where('school_id', $school->id)->when($term, fn ($q) => $q->where('term_id', $term->id))->where('direction', 'debit')->sum('amount');
            $months = collect(range(5, 0))->map(fn ($offset) => now()->subMonths($offset)->startOfMonth());
            $payments = FeePayment::where('school_id', $school->id)->whereDate('paid_at', '>=', $months->first())->get();
            $expenseRows = Expense::where('school_id', $school->id)->whereDate('expense_date', '>=', $months->first())->get();

            $finance = [
                'expected' => $expectedFees + $arrears, 'income' => $income, 'expenses' => $expenses,
                'outstanding' => max(0, $expectedFees + $arrears - $income), 'poolBalance' => $credits - $debits, 'net' => $income - $expenses,
                'labels' => $months->map(fn ($month) => $month->format('M Y'))->values(),
                'incomeSeries' => $months->map(fn ($month) => (float) $payments->filter(fn ($payment) => $payment->paid_at?->isSameMonth($month))->sum('amount'))->values(),
                'expenseSeries' => $months->map(fn ($month) => (float) $expenseRows->filter(fn ($expense) => $expense->expense_date?->isSameMonth($month))->sum('amount'))->values(),
                'recentPayments' => FeePayment::with('student:id,name')->where('school_id', $school->id)->latest('paid_at')->take(6)->get(),
                'recentExpenses' => Expense::where('school_id', $school->id)->latest('expense_date')->take(6)->get(),
            ];
        }

        $teacher = [];
        if ($isTeacherWorkspace) {
            $assignments = DB::table('staff_subjects')
                ->join('subjects', 'subjects.id', '=', 'staff_subjects.subject_id')
                ->leftJoin('school_classes', 'school_classes.id', '=', 'staff_subjects.school_class_id')
                ->where('staff_subjects.school_id', $school->id)
                ->where('staff_subjects.user_id', $user->id)
                ->when($term, fn ($query) => $query->where(fn ($scope) => $scope->where('staff_subjects.term_id', $term->id)->orWhereNull('staff_subjects.term_id')))
                ->orderBy('subjects.name')
                ->get(['staff_subjects.subject_id','staff_subjects.school_class_id','subjects.name as subject','subjects.code','school_classes.name as class']);

            $classIds = $assignments->pluck('school_class_id')->filter()->unique()->values();
            $days = collect(range(6, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
            $attendance = AttendanceRecord::where('school_id', $school->id)->where('recorded_by', $user->id)
                ->when($term, fn ($query) => $query->where('term_id', $term->id))
                ->whereDate('attendance_date', '>=', $days->first())->get();

            $performance = DB::table('exam_marks')
                ->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
                ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')
                ->join('subjects', 'subjects.id', '=', 'exam_papers.subject_id')
                ->where('exams.school_id', $school->id)
                ->when($term, fn ($query) => $query->where('exams.term_id', $term->id))
                ->whereExists(function ($query) use ($user, $term) {
                    $query->selectRaw('1')->from('staff_subjects')
                        ->whereColumn('staff_subjects.subject_id', 'exam_papers.subject_id')
                        ->whereColumn('staff_subjects.school_class_id', 'exams.school_class_id')
                        ->where('staff_subjects.user_id', $user->id)
                        ->when($term, fn ($q) => $q->where(fn ($scope) => $scope->where('staff_subjects.term_id', $term->id)->orWhereNull('staff_subjects.term_id')));
                })
                ->whereNotNull('exam_marks.score')
                ->selectRaw('subjects.name, ROUND(AVG(exam_marks.score / exam_papers.maximum_score * 100), 1) as average')
                ->groupBy('subjects.id', 'subjects.name')->orderBy('subjects.name')->get();

            $pendingPapers = DB::table('exam_papers')
                ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')
                ->leftJoin('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
                ->where('exams.school_id', $school->id)
                ->when($term, fn ($query) => $query->where('exams.term_id', $term->id))
                ->whereExists(function ($query) use ($user, $term) {
                    $query->selectRaw('1')->from('staff_subjects')
                        ->whereColumn('staff_subjects.subject_id', 'exam_papers.subject_id')
                        ->whereColumn('staff_subjects.school_class_id', 'exams.school_class_id')
                        ->where('staff_subjects.user_id', $user->id)
                        ->when($term, fn ($q) => $q->where(fn ($scope) => $scope->where('staff_subjects.term_id', $term->id)->orWhereNull('staff_subjects.term_id')));
                })
                ->where(fn ($query) => $query->whereNull('exam_paper_submissions.id')->orWhereIn('exam_paper_submissions.status', ['draft','rejected']))
                ->count();

            $todayLessons = DB::table('timetable_slots')
                ->leftJoin('subjects', 'subjects.id', '=', 'timetable_slots.subject_id')
                ->leftJoin('school_classes', 'school_classes.id', '=', 'timetable_slots.school_class_id')
                ->leftJoin('streams', 'streams.id', '=', 'timetable_slots.stream_id')
                ->where('timetable_slots.school_id', $school->id)->where('timetable_slots.user_id', $user->id)
                ->when($term, fn ($query) => $query->where('timetable_slots.term_id', $term->id))
                ->where('timetable_slots.day_of_week', now()->format('l'))->orderBy('timetable_slots.starts_at')
                ->get(['timetable_slots.starts_at','timetable_slots.ends_at','timetable_slots.label','subjects.name as subject','school_classes.name as class','streams.name as stream']);

            $teacher = [
                'assignments' => $assignments, 'subjects' => $assignments->pluck('subject_id')->unique()->count(), 'classes' => $classIds->count(),
                'learners' => $classIds->isEmpty() ? 0 : DB::table('students')->where('school_id', $school->id)->where('status', 'active')->whereIn('school_class_id', $classIds)->count(),
                'lessonsToday' => $todayLessons->count(), 'todayLessons' => $todayLessons, 'attendanceToday' => $attendance->filter(fn ($row) => $row->attendance_date?->isToday())->count(),
                'pendingPapers' => $pendingPapers, 'attendanceLabels' => $days->map(fn ($day) => $day->format('D'))->values(),
                'presentSeries' => $days->map(fn ($day) => $attendance->filter(fn ($row) => $row->attendance_date?->isSameDay($day))->whereIn('status', ['present','late'])->count())->values(),
                'absentSeries' => $days->map(fn ($day) => $attendance->filter(fn ($row) => $row->attendance_date?->isSameDay($day))->where('status', 'absent')->count())->values(),
                'performanceLabels' => $performance->pluck('name')->values(), 'performanceSeries' => $performance->pluck('average')->map(fn ($value) => (float) $value)->values(),
            ];
        }

        $events = SchoolEvent::where('school_id', $school->id)->whereDate('event_date', '>=', today())->orderBy('event_date')->take(4)->get();
        $pageTitle = $isFinanceWorkspace ? 'Bursar Dashboard' : ($isTeacherWorkspace ? 'Teacher Dashboard' : 'Staff Workbench');

        return view('livewire.staff-workbench', compact('school', 'term', 'isFinanceWorkspace', 'isTeacherWorkspace', 'visibleModules', 'finance', 'teacher', 'events'), compact('pageTitle'));
    }
}