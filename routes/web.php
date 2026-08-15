<?php

use App\Livewire\FeePayments;
use App\Livewire\Expenses;
use App\Livewire\Attendance;
use App\Livewire\AttendanceReport;
use App\Livewire\SubjectAttendance;
use App\Livewire\ClassesAndStreams;
use App\Livewire\StaffManagement;
use App\Livewire\StaffAttendance;
use App\Livewire\SchoolSettingsV2 as SchoolSettings;
use App\Livewire\StaffRegister;
use App\Livewire\Payroll;
use App\Livewire\Leaves;
use App\Livewire\Designations;
use App\Livewire\Licensing;
use App\Livewire\PortalHome;
use App\Livewire\StaffWorkbench;
use App\Livewire\Reports;
use App\Livewire\Subjects;
use App\Livewire\StudentSubjectSelections;
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
use App\Livewire\EventsV2 as Events;
use App\Livewire\Communications;
use App\Livewire\StudentTermReport;
use App\Livewire\ReportSettings;
use App\Livewire\BulkTermReportsV3 as BulkTermReports;
use App\Livewire\PromotionsV2 as Promotions;
use App\Livewire\FeeStructures;
use App\Livewire\StudentCategories;
use App\Livewire\StudentActivities;
use App\Livewire\AuditTrail;
use App\Livewire\StudentRegister;
use App\Livewire\StudentsIndex;
use App\Livewire\Graduates;
use App\Livewire\TermManagement;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DatabaseBackupController;
use App\Http\Controllers\PlatformAuthController;
use App\Http\Controllers\PlatformSchoolController;
use App\Http\Controllers\PlatformSchoolSmsController;
use App\Http\Controllers\PlatformSchoolImportController;
use App\Http\Controllers\PlatformLandingPageController;
use App\Http\Controllers\LandingPageController;
use App\Http\Controllers\PlatformSupportController;
use App\Http\Controllers\PlatformOperationsController;
use Illuminate\Support\Facades\DB;
use App\Models\Arrears;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\StudentEnrolment;
use App\Models\ContactMessage;
use App\Models\AttendanceRecord;
use App\Http\Controllers\StudentActivityExportController;
use App\Http\Controllers\SchoolOperationsController;
use App\Http\Controllers\PlatformSchoolGroupController;
use App\Http\Controllers\BranchContextController;
use App\Http\Controllers\GroupDashboardController;
use App\Http\Controllers\StudentIdCardController;
use App\Http\Controllers\ReportExportController;
use App\Http\Controllers\GraduationCertificateController;
use Livewire\Volt\Volt;


Route::prefix('platform')->name('platform.')->group(function () {
    Route::middleware('guest:platform')->group(function () {
        Route::get('login', [PlatformAuthController::class, 'showLogin'])->name('login');
        Route::post('login', [PlatformAuthController::class, 'login'])->middleware('throttle:10,1')->name('login.store');
    });
    Route::middleware('auth:platform')->group(function () {
        Route::get('setup', [PlatformAuthController::class, 'showSetup'])->name('setup');
        Route::post('setup', [PlatformAuthController::class, 'confirmSetup'])->middleware('throttle:10,1')->name('setup.confirm');
        Route::get('challenge', [PlatformAuthController::class, 'showChallenge'])->name('challenge');
        Route::post('challenge', [PlatformAuthController::class, 'challenge'])->middleware('throttle:10,1')->name('challenge.verify');
        Route::post('logout', [PlatformAuthController::class, 'logout'])->name('logout');
    });
    Route::middleware('platform.mfa')->group(function () {
        Route::get('mfa/reset', [PlatformAuthController::class, 'showMfaReset'])->name('mfa.reset');
        Route::post('mfa/reset', [PlatformAuthController::class, 'resetMfa'])->middleware('throttle:5,1')->name('mfa.reset.store');
        Route::get('/', [PlatformAuthController::class, 'dashboard'])->name('dashboard');
        Route::middleware('platform.role:platform_owner,operations_admin')->group(function () {
        Route::get('backups/download', DatabaseBackupController::class)->middleware('platform.role:platform_owner')->name('backups.download');
        Route::get('schools', [PlatformSchoolController::class, 'index'])->name('schools');
        Route::get('groups', [PlatformSchoolGroupController::class, 'index'])->name('groups.index');
        Route::post('groups', [PlatformSchoolGroupController::class, 'store'])->name('groups.store');
        Route::get('groups/{schoolGroup}', [PlatformSchoolGroupController::class, 'show'])->name('groups.show');
        Route::post('groups/{schoolGroup}/branches', [PlatformSchoolGroupController::class, 'addBranch'])->name('groups.branches.store');
        Route::post('groups/{schoolGroup}/access', [PlatformSchoolGroupController::class, 'grantAccess'])->name('groups.access.store');
        Route::get('schools/create', [PlatformSchoolController::class, 'create'])->name('schools.create');
        Route::post('schools', [PlatformSchoolController::class, 'store'])->name('schools.store');
        Route::get('schools/{school}', [PlatformSchoolController::class, 'show'])->name('schools.show');
        Route::get('schools/{school}/edit', [PlatformSchoolController::class, 'edit'])->name('schools.edit');
        Route::put('schools/{school}', [PlatformSchoolController::class, 'update'])->name('schools.update');
        Route::put('schools/{school}/sms-configuration', [PlatformSchoolSmsController::class, 'update'])->name('schools.sms-configuration.update');
        Route::delete('schools/{school}', [PlatformSchoolController::class, 'destroy'])->middleware('platform.role:platform_owner')->name('schools.destroy');
        Route::post('schools/{school}/imports/students', [PlatformSchoolImportController::class, 'students'])->name('schools.imports.students');
        Route::post('schools/{school}/imports/teachers', [PlatformSchoolImportController::class, 'teachers'])->name('schools.imports.teachers');
        Route::get('imports/templates/{type}', [PlatformSchoolImportController::class, 'template'])->name('imports.template');
        Route::get('licences', [PlatformSchoolController::class, 'licences'])->name('licences');
        Route::patch('licences/{school}', [PlatformSchoolController::class, 'updateLicence'])->name('licences.update');
        Route::get('website', [PlatformLandingPageController::class, 'edit'])->name('website.edit');
        Route::put('website', [PlatformLandingPageController::class, 'update'])->name('website.update');
        Route::get('billing', [PlatformOperationsController::class, 'billing'])->name('billing');
        Route::get('audit', [PlatformOperationsController::class, 'audit'])->name('audit');
        Route::get('settings', [PlatformOperationsController::class, 'settings'])->name('settings');
        Route::put('settings', [PlatformOperationsController::class, 'updateSettings'])->name('settings.update');
        });
        Route::middleware('platform.role:platform_owner,support_admin')->group(function () {
        Route::get('support', [PlatformSupportController::class, 'index'])->name('support.index');
        Route::get('support/{contactMessage}', [PlatformSupportController::class, 'show'])->name('support.show');
        Route::post('support/{contactMessage}/reply', [PlatformSupportController::class, 'reply'])->name('support.reply');
        Route::patch('support/{contactMessage}/status', [PlatformSupportController::class, 'toggleStatus'])->name('support.status');
        });
        Route::middleware('platform.role:platform_owner')->group(function () {
        Route::get('administrators', [PlatformOperationsController::class, 'administrators'])->name('administrators');
        Route::post('administrators', [PlatformOperationsController::class, 'storeAdministrator'])->name('administrators.store');
        Route::patch('administrators/{platformAdmin}', [PlatformOperationsController::class, 'updateAdministrator'])->name('administrators.update');
        });
    });
});
Route::get('/', LandingPageController::class)->name('home');
Route::view('privacy', 'legal.privacy')->name('privacy');
Route::view('terms', 'legal.terms')->name('terms');
Route::post('contact', function (\Illuminate\Http\Request $request) { $data=$request->validate(['name'=>'required|string|max:255','email'=>'required|email|max:255','subject'=>'required|string|max:255','message'=>'required|string|max:5000','type'=>'nullable|in:contact,issue']); ContactMessage::create($data); return back()->with('contact_status','Thank you. The Edlink team will get back to you shortly.'); })->middleware('throttle:5,1')->name('contact.store');

Route::get('dashboard', function () {
    if (! auth()->user()->isSuperadmin() && auth()->user()->role !== 'admin') return redirect()->route('workbench.home');
    $school = auth()->user()->school()->with(['classes.streams', 'classes.students', 'terms', 'users'])->first();
    $monthStart = now()->startOfMonth();
    $requestedTermId = (int) request('dashboard_term');
    $term = $school?->terms?->firstWhere('id', $requestedTermId) ?? $school?->currentTerm();
    $expectedFees = $term ? (float) StudentEnrolment::where('student_enrolments.school_id', $school->id)
        ->where('student_enrolments.term_id', $term->id)
        ->whereHas('student', fn ($query) => $query->where('status', 'active'))
        ->sum('base_fee_amount') : 0;
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
    try {
        $attendanceEndDate = request()->filled('attendance_date') ? \Carbon\Carbon::parse((string) request('attendance_date'))->startOfDay() : today();
    } catch (\Throwable) {
        $attendanceEndDate = today();
    }
    $weekDays = collect(range(4, 0))->map(fn ($offset) => $attendanceEndDate->copy()->subDays($offset));
    $attendanceRecords = $term ? AttendanceRecord::where('school_id',$school->id)->where('term_id',$term->id)->whereDate('attendance_date','>=',$weekDays->first())->get() : collect();
    $attendanceSeries = ['all' => ['present'=>[], 'absent'=>[]]];
    foreach ($weekDays as $day) { $dayRecords=$attendanceRecords->filter(fn($r)=>$r->attendance_date->isSameDay($day)); $attendanceSeries['all']['present'][]=$dayRecords->whereIn('status',['present','late'])->count(); $attendanceSeries['all']['absent'][]=$dayRecords->where('status','absent')->count(); }
    foreach ($school?->classes ?? [] as $class) { $ids=$class->students->pluck('id'); $attendanceSeries[$class->id]=['present'=>[],'absent'=>[]]; foreach($weekDays as $day){$records=$attendanceRecords->whereIn('student_id',$ids)->filter(fn($r)=>$r->attendance_date->isSameDay($day));$attendanceSeries[$class->id]['present'][]=$records->whereIn('status',['present','late'])->count();$attendanceSeries[$class->id]['absent'][]=$records->where('status','absent')->count();} }
    $genderCounts = $school ? $school->students()->where('status','active')->selectRaw("lower(gender) as gender, count(*) as total")->groupBy('gender')->pluck('total','gender') : collect();
    $paymentRows = $school ? FeePayment::where('school_id',$school->id)->whereNotNull('paid_at')->get(['amount','paid_at']) : collect();
    $paymentYears = collect([now()->year, now()->year - 1])
        ->merge($paymentRows->map(fn($payment)=>(int)$payment->paid_at->year))
        ->unique()->sortDesc()->values();
    $paymentTrendByYear = $paymentYears->mapWithKeys(function($year)use($paymentRows){
        $yearPayments=$paymentRows->filter(fn($payment)=>(int)$payment->paid_at->year===(int)$year);
        return [(string)$year=>collect(range(1,12))->map(fn($month)=>(float)$yearPayments->filter(fn($payment)=>$payment->paid_at->month===$month)->sum('amount'))->values()];
    });
    $performance = $term ? DB::table('exam_marks')->join('exam_papers','exam_papers.id','=','exam_marks.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->join('exam_paper_submissions','exam_paper_submissions.exam_paper_id','=','exam_papers.id')->where('exams.school_id',$school->id)->where('exams.term_id',$term->id)->where('exam_paper_submissions.status','approved')->whereNotNull('exam_marks.score')->selectRaw('exams.name, exams.school_class_id, ROUND(AVG(exam_marks.score * 100.0 / NULLIF(exam_papers.maximum_score,0)),2) as average')->groupBy('exams.id','exams.name','exams.school_class_id')->orderBy('exams.created_at')->get() : collect();
    $buildPerformanceSeries = function($rows){
        $grouped=collect($rows)->groupBy('name');
        return ['labels'=>$grouped->keys()->values(),'data'=>$grouped->map(fn($exams)=>round($exams->avg(fn($exam)=>(float)$exam->average),2))->values()];
    };
    $performanceSeries=['all'=>$buildPerformanceSeries($performance)];
    foreach($school?->classes ?? [] as $class)$performanceSeries[(string)$class->id]=$buildPerformanceSeries($performance->where('school_class_id',$class->id));
    $events = $school ? DB::table('school_events')->where('school_id',$school->id)->whereDate('event_date','>=',today())->orderBy('event_date')->limit(6)->get(['title','event_date','type']) : collect();
    $dashboardClasses = $school?->classes?->sortBy('name')->values() ?? collect();
    $dashboardClass = $dashboardClasses->firstWhere('id', (int) request('timetable_class')) ?? $dashboardClasses->first();
    $timetable = $school && $term && $dashboardClass ? DB::table('timetable_slots')->leftJoin('subjects','subjects.id','=','timetable_slots.subject_id')->where('timetable_slots.school_id',$school->id)->where('timetable_slots.term_id',$term->id)->where('timetable_slots.school_class_id',$dashboardClass->id)->orderBy('starts_at')->get(['day_of_week','starts_at','ends_at','label','subjects.name as subject']) : collect();
    $homeworkReminders = $school && $term ? DB::table('homework_assignments')->join('school_classes','school_classes.id','=','homework_assignments.school_class_id')->where('homework_assignments.school_id',$school->id)->where('homework_assignments.term_id',$term->id)->whereNotNull('published_at')->whereBetween('due_at',[now(),now()->addDays(14)])->orderBy('due_at')->limit(5)->get(['homework_assignments.title','homework_assignments.due_at','school_classes.name as class_name'])->map(fn($item)=>['icon'=>'H','text'=>'Homework: '.$item->title.' ('.$item->class_name.')','due'=>\Carbon\Carbon::parse($item->due_at)->diffForHumans()]) : collect();
    $eventReminders = $events->take(5)->map(fn($event)=>['icon'=>'E','text'=>$event->title,'due'=>\Carbon\Carbon::parse($event->event_date)->startOfDay()->diffForHumans()]);
    $dashboardReminders = $eventReminders->concat($homeworkReminders);
    if ($school?->license_expires_at) $dashboardReminders->push(['icon'=>'L','text'=>ucfirst($school->license_plan).' licence renewal','due'=>$school->license_expires_at->diffForHumans()]);
    $dashboardReminders = $dashboardReminders->take(8)->values();
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
        'studentCount' => $school?->students()->where('status', 'active')->count() ?? 0,
        'classCount' => $school?->classes()->count() ?? 0,
        'streamCount' => $school?->streams()->count() ?? 0,
        'staffCount' => $school?->users()->count() ?? 0,
        'studentsAddedThisMonth' => $school?->students()->where('status', 'active')->where('created_at', '>=', $monthStart)->count() ?? 0,
        'classesAddedThisMonth' => $school?->classes()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'streamsAddedThisMonth' => $school?->streams()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'staffAddedThisMonth' => $school?->users()->where('created_at', '>=', $monthStart)->count() ?? 0,
        'attendanceLabels' => $weekDays->map(fn($day)=>$day->format('D, d M'))->values(),
        'attendanceSeries' => $attendanceSeries,
        'attendanceDate' => $attendanceEndDate->toDateString(),
        'genderData' => [(int)($genderCounts['male'] ?? $genderCounts['m'] ?? 0),(int)($genderCounts['female'] ?? $genderCounts['f'] ?? 0)],
        'paymentYears' => $paymentYears, 'paymentTrendByYear' => $paymentTrendByYear,
        'performanceSeries' => $performanceSeries,
        'dashboardEvents' => $events, 'dashboardTimetable' => $timetable, 'dashboardTimetableClass' => $dashboardClass?->name, 'dashboardTimetableClassId' => $dashboardClass?->id, 'dashboardTimetableClasses' => $dashboardClasses, 'dashboardReminders' => $dashboardReminders, 'dashboardNotifications' => $notifications,
        'currencySymbol' => $currencySymbol, 'dashboardDebtors' => $debtors,
    ]);
})->middleware(['auth', 'verified', 'branch.context', 'active.user'])->name('dashboard');

Route::middleware(['auth', 'verified', 'branch.context', 'active.user', 'designation.access'])->group(function () {
    Route::put('branch-context', [BranchContextController::class, 'update'])->name('branch-context.update');
    Route::get('group-dashboard', GroupDashboardController::class)->name('group-dashboard');
    Volt::route('profile', 'pages.profile')->name('profile');
    Route::get('portal', PortalHome::class)->name('portal.home');
    Route::get('workbench', StaffWorkbench::class)->name('workbench.home');
    Route::get('homework', \App\Livewire\Homework::class)->name('homework.index');
    Route::get('homework/assignments/{assignment}/download', function (\App\Models\HomeworkAssignment $assignment) {
        $user=auth()->user();
        $student=$user->role==='student' ? $user->portalStudents()->where('students.school_id',$user->school_id)->first() : null;
        $canView=$assignment->school_id===$user->school_id && (
            $assignment->teacher_id===$user->id
            || in_array($user->role,['admin','superadmin','academic_admin'],true)
            || ($student
                && $assignment->published_at
                && $student->school_class_id===$assignment->school_class_id
                && (!$assignment->stream_id || $student->stream_id===$assignment->stream_id)
                && \App\Services\StudentSubjectSelectionService::studentTakesSubject($student,$assignment->term_id,$assignment->subject_id))
        );
        abort_unless($canView && $assignment->attachment_path,404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download($assignment->attachment_path,$assignment->attachment_name);
    })->name('homework.assignment.download');
    Route::get('homework/submissions/{submission}/download', function (\App\Models\HomeworkSubmission $submission) {
        $submission->load('assignment');
        $user=auth()->user();
        $student=$user->role==='student' ? $user->portalStudents()->where('students.school_id',$user->school_id)->first() : null;
        $canView=$submission->assignment->school_id===$user->school_id && (
            $submission->assignment->teacher_id===$user->id
            || in_array($user->role,['admin','superadmin','academic_admin'],true)
            || ($student && $student->id===$submission->student_id)
        );
        abort_unless($canView && $submission->attachment_path,404);
        return \Illuminate\Support\Facades\Storage::disk('local')->download($submission->attachment_path,$submission->attachment_name);
    })->name('homework.submission.download');
    Route::get('students/register', StudentRegister::class)->name('students.register');
    Route::get('students', StudentsIndex::class)->name('students.index');
    Route::get('students/graduates', Graduates::class)->name('graduates.index');
    Route::get('students/graduates/{graduationRecord}/certificate', GraduationCertificateController::class)->name('graduates.certificate');
    Route::get('students/id-cards', StudentIdCardController::class)->name('students.id-cards');
    Route::get('students/categories', StudentCategories::class)->name('student-categories.index');
    Route::get('students/activities', StudentActivities::class)->name('students.activities');
    Route::get('students/activities/{type}/{activity}/export', StudentActivityExportController::class)->name('students.activities.export');
    Route::get('finance/terms', TermManagement::class)->name('terms.index');
    Route::get('finance/fee-structure', FeeStructures::class)->name('fee-structures.index');
    Route::get('finance/payments', FeePayments::class)->name('fee-payments.index');
    Route::get('finance/payments/{payment}/receipt', function (FeePayment $payment) {
        $user = auth()->user();
        $isFinanceStaff = in_array($user->role, ['admin', 'superadmin'], true) || $user->hasPermission('finance.payments');
        $isLinkedPortalUser = in_array($user->role, ['parent', 'student'], true)
            && ($user->portalStudents()->whereKey($payment->student_id)->exists()
                || ($user->role === 'parent' && $payment->student->guardians()->where('email', $user->email)->exists()));
        abort_unless($payment->school_id === $user->school_id && ($isFinanceStaff || $isLinkedPortalUser), 404);
        return view('finance.payment-receipt', ['payment' => $payment->load(['student.schoolClass', 'term', 'recordedBy']), 'school' => auth()->user()->school]);
    })->name('fee-payments.receipt');
    Route::get('finance/expenses', Expenses::class)->name('expenses.index');
    Route::get('finance/ledger', [SchoolOperationsController::class, 'finance'])->name('finance.ledger');
    Route::post('finance/ledger/{entry}/approve', [SchoolOperationsController::class, 'approve'])->name('finance.ledger.approve');
    Route::post('finance/ledger/{entry}/reject', [SchoolOperationsController::class, 'reject'])->name('finance.ledger.reject');
    Route::post('finance/accounts', [SchoolOperationsController::class, 'storeAccount'])->name('finance.accounts.store');
    Route::post('finance/transfers', [SchoolOperationsController::class, 'storeTransfer'])->name('finance.transfers.store');
    Route::post('finance/transfers/{transfer}/approve', [SchoolOperationsController::class, 'approveTransfer'])->name('finance.transfers.approve');
    Route::post('finance/reconciliations/{reconciliation}/reopen', [SchoolOperationsController::class, 'reopenReconciliation'])->name('finance.reconciliations.reopen');
    Route::post('finance/ledger/{entry}/reverse', [SchoolOperationsController::class, 'reverse'])->name('finance.ledger.reverse');
    Route::post('finance/reconcile', [SchoolOperationsController::class, 'reconcile'])->name('finance.ledger.reconcile');
    Route::get('attendance', Attendance::class)->name('attendance.index');
    Route::get('attendance/reports', AttendanceReport::class)->name('attendance.reports');
    Route::get('attendance/subject', SubjectAttendance::class)->name('attendance.subject');
    Route::get('academics/classes', ClassesAndStreams::class)->name('classes.index');
    Route::get('staff', StaffManagement::class)->name('staff.index');
    Route::get('staff/register', StaffRegister::class)->name('staff.register');
    Route::get('staff/attendance', StaffAttendance::class)->name('staff.attendance');
    Route::get('staff/payroll', Payroll::class)->name('payroll.index');
    Route::get('staff/leaves', Leaves::class)->name('leaves.index');
    Route::get('staff/designations', Designations::class)->name('designations.index');
    Route::get('settings/licence', Licensing::class)->name('licence.index');
    Route::get('reports', Reports::class)->name('reports.index');
    Route::get('reports/exports/{reportExport}', ReportExportController::class)->name('reports.exports.download');
    Route::get('academics/subjects', Subjects::class)->name('subjects.index');
    Route::get('academics/subject-selection', StudentSubjectSelections::class)->name('subject-selections.index');
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
    Route::get('reports/student-term-report/{student}/{exam}', StudentTermReport::class)->name('reports.student-term-report.show');
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
        return redirect()->route('reports.student-term-report.show', [$student, $exam]);
    })->name('exams.report-card');
    Route::get('settings/audit-trail', AuditTrail::class)->name('settings.audit-trail');
    Route::get('settings/privacy-requests', [SchoolOperationsController::class, 'privacy'])->name('privacy.requests');
    Route::post('settings/privacy-requests', [SchoolOperationsController::class, 'createPrivacyRequest'])->name('privacy.requests.store');
    Route::post('settings/privacy-requests/{privacyRequest}/execute', [SchoolOperationsController::class, 'executePrivacyRequest'])->name('privacy.requests.execute');
    Route::get('settings', SchoolSettings::class)->name('settings.index');
});

require __DIR__.'/auth.php';
