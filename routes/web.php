<?php

use App\Livewire\FeePayments;
use App\Livewire\Expenses;
use App\Livewire\Attendance;
use App\Livewire\AttendanceReport;
use App\Livewire\ClassesAndStreams;
use App\Livewire\StaffManagement;
use App\Livewire\SchoolSettings;
use App\Livewire\StaffRegister;
use App\Livewire\Payroll;
use App\Livewire\Leaves;
use App\Livewire\Designations;
use App\Livewire\Licensing;
use App\Livewire\PortalHome;
use App\Livewire\StaffWorkbench;
use App\Livewire\Reports;
use App\Livewire\Subjects;
use App\Livewire\ExamSetup;
use App\Livewire\MarksEntry;
use App\Livewire\GradingScales;
use App\Livewire\ExamResults;
use App\Livewire\ResultAccessSettings;
use App\Livewire\MyResults;
use App\Livewire\PortalAccess;
use App\Livewire\ParentRegister;
use App\Livewire\ParentsIndex;
use App\Livewire\Timetable;
use App\Livewire\Events;
use App\Livewire\Communications;
use App\Livewire\StudentTermReport;
use App\Livewire\ReportSettings;
use App\Livewire\BulkTermReports;
use App\Livewire\Promotions;
use App\Livewire\FeeStructures;
use App\Livewire\StudentCategories;
use App\Livewire\StudentRegister;
use App\Livewire\StudentsIndex;
use App\Livewire\TermManagement;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\DB;
use App\Models\Arrears;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\StudentEnrolment;
use App\Models\ContactMessage;
use App\Models\AttendanceRecord;
use Livewire\Volt\Volt;

Route::get('/', function () {
    return view('welcome');
});
Route::post('contact', function (\Illuminate\Http\Request $request) { $data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255','subject'=>'required|string|max:255','message'=>'required|string|max:5000','type'=>'nullable|in:contact,issue']); ContactMessage::create($data); return back()->with('contact_status','Thank you. The Edlink team will get back to you shortly.'); })->name('contact.store');

Route::get('dashboard', function () {
    $school = auth()->user()->school()->with(['classes.streams', 'classes.students', 'terms', 'users'])->first();
    $monthStart = now()->startOfMonth();
    $term = $school?->currentTerm();
    $expectedFees = $term ? (float) $school->students()->where('status', 'active')->get()->sum(fn ($student) => $student->mappedFeeAmount($term) ?? 0) : 0;
    $arrears = $term ? (float) Arrears::where('school_id', $school->id)->where('applied_term_id', $term->id)->whereHas('student', fn ($query) => $query->where('status', 'active'))->sum('amount') : 0;
    $feesPaid = $term ? (float) FeePayment::where('school_id', $school->id)->where('term_id', $term->id)->sum('amount') : 0;
    $termExpenses = $term ? (float) Expense::where('school_id', $school->id)->where('term_id', $term->id)->sum('amount') : 0;
    $poolCredits = $school ? (float) CashPoolEntry::where('school_id', $school->id)->where('direction', 'credit')->sum('amount') : 0;
    $poolDebits = $school ? (float) CashPoolEntry::where('school_id', $school->id)->where('direction', 'debit')->sum('amount') : 0;
    $cashFlow = $term ? CashPoolEntry::where('school_id', $school->id)->where('term_id', $term->id)->orderBy('transacted_at')->get()->groupBy(fn ($entry) => $entry->transacted_at->format('M Y')) : collect();
    $activeLearners = $school ? $school->students()->where('status', 'active')->count() : 0;
    $attendanceToday = $term ? AttendanceRecord::where('school_id', $school->id)->where('term_id', $term->id)->whereDate('attendance_date', today())->get() : collect();
    $presentToday = $attendanceToday->whereIn('status', ['present', 'late'])->count();
    $currencyCode = \App\Models\SchoolSetting::where(['school_id'=>$school->id,'key'=>'currency'])->value('value') ?: 'UGX';
    $currencySymbol = ['UGX'=>'UGX','USD'=>'$','KES'=>'KSh','TZS'=>'TSh','RWF'=>'FRw'][$currencyCode] ?? $currencyCode;
    $debtors = $term ? $school->students()->with(['schoolClass','guardians'])->where('status','active')->get()->map(fn($student)=>['id'=>$student->id,'name'=>$student->name,'class'=>$student->schoolClass?->name ?? '—','balance'=>max(0,$student->balance($term)),'guardian_email'=>$student->guardians->first()?->email])->filter(fn($student)=>$student['balance']>0)->sortByDesc('balance')->take(6)->values() : collect();
    $weekDays = collect(range(4, 0))->map(fn ($offset) => now()->subDays($offset)->startOfDay());
    $attendanceRecords = $term ? AttendanceRecord::where('school_id',$school->id)->where('term_id',$term->id)->whereDate('attendance_date','>=',$weekDays->first())->get() : collect();
    $attendanceSeries = ['all' => ['present'=>[], 'absent'=>[]]];
    foreach ($weekDays as $day) { $dayRecords=$attendanceRecords->filter(fn($r)=>$r->attendance_date->isSameDay($day)); $attendanceSeries['all']['present'][]=$dayRecords->whereIn('status',['present','late'])->count(); $attendanceSeries['all']['absent'][]=$dayRecords->where('status','absent')->count(); }
    foreach ($school?->classes ?? [] as $class) { $ids=$class->students->pluck('id'); $attendanceSeries[$class->id]=['present'=>[],'absent'=>[]]; foreach($weekDays as $day){$records=$attendanceRecords->whereIn('student_id',$ids)->filter(fn($r)=>$r->attendance_date->isSameDay($day));$attendanceSeries[$class->id]['present'][]=$records->whereIn('status',['present','late'])->count();$attendanceSeries[$class->id]['absent'][]=$records->where('status','absent')->count();} }
    $genderCounts = $school ? $school->students()->where('status','active')->selectRaw("lower(gender) as gender, count(*) as total")->groupBy('gender')->pluck('total','gender') : collect();
    $paymentTrend = $school ? FeePayment::where('school_id',$school->id)->whereYear('paid_at',now()->year)->get()->groupBy(fn ($payment) => $payment->paid_at->month)->map(fn ($payments) => $payments->sum('amount')) : collect();
    $performance = $term ? DB::table('exam_marks')->join('exam_papers','exam_papers.id','=','exam_marks.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->join('exam_paper_submissions','exam_paper_submissions.exam_paper_id','=','exam_papers.id')->where('exams.school_id',$school->id)->where('exams.term_id',$term->id)->where('exam_paper_submissions.status','approved')->selectRaw('exams.name, ROUND(AVG(exam_marks.score / exam_papers.maximum_score * 100),2) as average')->groupBy('exams.id','exams.name')->orderBy('exams.created_at')->get() : collect();
    $events = $school ? DB::table('school_events')->where('school_id',$school->id)->whereDate('event_date','>=',today())->orderBy('event_date')->limit(6)->get(['title','event_date','type']) : collect();
    $dashboardClass = $school?->classes->first();
    $timetable = $school && $term && $dashboardClass ? DB::table('timetable_slots')->leftJoin('subjects','subjects.id','=','timetable_slots.subject_id')->where('timetable_slots.school_id',$school->id)->where('timetable_slots.term_id',$term->id)->where('timetable_slots.school_class_id',$dashboardClass->id)->orderBy('starts_at')->get(['day_of_week','starts_at','ends_at','label','subjects.name as subject']) : collect();
    $notifications = $school ? DB::table('school_notifications')->where('school_id',$school->id)->latest()->limit(6)->get(['title','message','type','created_at']) : collect();

    return view('dashboard', [
        'school' => $school,
        'term' => $term,
        'expectedFees' => $expectedFees,
        'arrearsAmount' => $arrears,
        'totalExpected' => $expectedFees + $arrears,
        'feesPaid' => $feesPaid,
        'pendingFees' => max(0, $expectedFees + $arrears - $feesPaid),
        'termExpenses' => $termExpenses,
        'poolBalance' => $poolCredits - $poolDebits,
        'cashFlowLabels' => $cashFlow->keys()->values(),
        'cashFlowIncome' => $cashFlow->map(fn ($entries) => (float) $entries->where('direction', 'credit')->sum('amount'))->values(),
        'cashFlowExpenditure' => $cashFlow->map(fn ($entries) => (float) $entries->where('direction', 'debit')->sum('amount'))->values(),
        'activeLearners' => $activeLearners,
        'presentToday' => $presentToday,
        'absentToday' => $attendanceToday->where('status', 'absent')->count(),
        'attendanceRateToday' => $activeLearners ? round(($presentToday / $activeLearners) * 100, 1) : 0,
        'studentCount' => $school?->students()->count() ?? 0,
        'classCount' => $school?->classes()->count() ?? 0,
        'streamCount' => $school?->streams()->count() ?? 0,
        'staffCount' => $school?->users()->count() ?? 0,
        'studentsAddedThisMonth' => $school?->students()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'classesAddedThisMonth' => $school?->classes()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'streamsAddedThisMonth' => $school?->streams()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'staffAddedThisMonth' => $school?->users()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'attendanceLabels' => $weekDays->map(fn($day)=>$day->format('D'))->values(),
        'attendanceSeries' => $attendanceSeries,
        'genderData' => [(int)($genderCounts['male'] ?? $genderCounts['m'] ?? 0),(int)($genderCounts['female'] ?? $genderCounts['f'] ?? 0)],
        'paymentTrend' => collect(range(1,12))->map(fn($month)=>(float)($paymentTrend[$month] ?? 0))->values(),
        'performanceLabels' => $performance->pluck('name')->values(), 'performanceData' => $performance->pluck('average')->map(fn($v)=>(float)$v)->values(),
        'dashboardEvents' => $events, 'dashboardTimetable' => $timetable, 'dashboardTimetableClass' => $dashboardClass?->name, 'dashboardNotifications' => $notifications,
        'currencySymbol' => $currencySymbol, 'dashboardDebtors' => $debtors,
    ]);
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'designation.access'])->group(function () {
    Volt::route('profile', 'pages.profile')->name('profile');
    Route::get('portal', PortalHome::class)->name('portal.home');
    Route::get('workbench', StaffWorkbench::class)->name('workbench.home');
    Route::get('students/register', StudentRegister::class)->name('students.register');
    Route::get('students', StudentsIndex::class)->name('students.index');
    Route::get('students/categories', StudentCategories::class)->name('student-categories.index');
    Route::get('finance/terms', TermManagement::class)->name('terms.index');
    Route::get('finance/fee-structure', FeeStructures::class)->name('fee-structures.index');
    Route::get('finance/payments', FeePayments::class)->name('fee-payments.index');
    Route::get('finance/payments/{payment}/receipt', function (FeePayment $payment) {
        abort_unless($payment->school_id === auth()->user()->school_id, 404);
        return view('finance.payment-receipt', ['payment' => $payment->load(['student.schoolClass', 'term', 'recordedBy']), 'school' => auth()->user()->school]);
    })->name('fee-payments.receipt');
    Route::get('finance/expenses', Expenses::class)->name('expenses.index');
    Route::get('attendance', Attendance::class)->name('attendance.index');
    Route::get('attendance/reports', AttendanceReport::class)->name('attendance.reports');
    Route::get('academics/classes', ClassesAndStreams::class)->name('classes.index');
    Route::get('staff', StaffManagement::class)->name('staff.index');
    Route::get('staff/register', StaffRegister::class)->name('staff.register');
    Route::get('staff/payroll', Payroll::class)->name('payroll.index');
    Route::get('staff/leaves', Leaves::class)->name('leaves.index');
    Route::get('staff/designations', Designations::class)->name('designations.index');
    Route::get('settings/licence', Licensing::class)->name('licence.index');
    Route::get('reports', Reports::class)->name('reports.index');
    Route::get('academics/subjects', Subjects::class)->name('subjects.index');
    Route::get('exams/setup', ExamSetup::class)->name('exams.setup');
    Route::get('exams/marks', MarksEntry::class)->name('exams.marks');
    Route::get('academics/grading-scales', GradingScales::class)->name('grading-scales.index');
    Route::get('exams/results', ExamResults::class)->name('exams.results');
    Route::get('my-results', MyResults::class)->name('my-results');
    Route::get('students/portal-access', PortalAccess::class)->name('students.portal-access');
    Route::get('parents/register', ParentRegister::class)->name('parents.register');
    Route::get('parents', ParentsIndex::class)->name('parents.index');
    Route::get('academics/timetable', Timetable::class)->name('timetable.index');
    Route::get('academics/events', Events::class)->name('events.index');
    Route::get('parents/communications', Communications::class)->name('communications.index');
    Route::get('reports/student-term-report', StudentTermReport::class)->name('reports.student-term-report');
    Route::get('reports/settings', ReportSettings::class)->name('reports.settings');
    Route::get('reports/bulk-term-reports', BulkTermReports::class)->name('reports.bulk-term-reports');
    Route::get('academics/promotions', Promotions::class)->name('promotions.index');
    Route::get('settings/result-access', ResultAccessSettings::class)->name('settings.result-access');
    Route::get('exams/{exam}/students/{student}/report-card', function (\App\Models\Exam $exam, \App\Models\Student $student) {
        $user = auth()->user();
        abort_unless(
            $exam->school_id === $user->school_id
            && $student->school_id === $exam->school_id
            && $student->school_class_id === $exam->school_class_id
            && (! $exam->stream_id || $student->stream_id === $exam->stream_id),
            404,
        );
        $isStaff = in_array($user->role, ['admin', 'academic_admin', 'teacher', 'registrar', 'bursar'], true);
        $isLinked = $user->portalStudents()->whereKey($student->id)->exists() || ($user->role === 'parent' && $student->guardians()->where('email', $user->email)->exists());
        abort_unless($isStaff || ($isLinked && $exam->isPublished()), 404);
        $school = $user->school;
        $blocked = \App\Models\SchoolSetting::where(['school_id' => $school->id, 'key' => 'results_fee_clearance_required'])->value('value') === 'enabled' && $student->balance($exam->term) > 0;
        $scales = \App\Models\GradingScale::where('school_id', $school->id)->get();
        $subjects = $exam->papers->filter(fn ($paper) => \Illuminate\Support\Facades\DB::table('exam_paper_submissions')->where('exam_paper_id', $paper->id)->value('status') === 'approved')->map(function ($paper) use ($student, $scales) { $score = (float) (\Illuminate\Support\Facades\DB::table('exam_marks')->where(['exam_paper_id' => $paper->id, 'student_id' => $student->id])->value('score') ?? 0); $percentage = $paper->maximum_score ? round($score / $paper->maximum_score * 100, 2) : 0; $grade = $scales->first(fn ($scale) => $percentage >= $scale->minimum_percentage && $percentage <= $scale->maximum_percentage); return compact('paper', 'score', 'percentage', 'grade'); });
        return view('exams.report-card', compact('school', 'exam', 'student', 'subjects', 'blocked'));
    })->name('exams.report-card');
    Route::get('settings', SchoolSettings::class)->name('settings.index');
});

require __DIR__.'/auth.php';
