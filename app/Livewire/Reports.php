<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\PayrollRun;
use App\Models\Student;
use App\Models\StaffAttendanceRecord;
use App\Models\StudentCategory;
use App\Models\Term;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Reports extends Component
{
    public string $report = 'student_register';
    public string $termId = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $gender = '';
    public string $categoryId = '';
    public string $debtorStatus = '';
    public int $page = 1;
    public int $perPage = 40;
    public bool $exporting = false;

    public function mount(): void
    {
        $term = Auth::user()->school->currentTerm();
        $this->termId = (string) ($term?->id ?? '');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->report = array_key_first($this->availableReports()) ?? 'student_register';
    }

    public function updatedReport(string $report): void
    {
        if (! array_key_exists($report, $this->availableReports())) {
            $this->report = array_key_first($this->availableReports()) ?? 'student_register';
        }
        $this->page = 1;
    }

    public function updated($property): void { if (in_array($property, ['report','termId','dateFrom','dateTo','gender','categoryId','debtorStatus'], true)) $this->page = 1; }
    public function previousPage(): void { $this->page = max(1, $this->page - 1); }
    public function nextPage(): void { $this->page++; }
    public function goToPage(int $page): void { $this->page = max(1, $page); }
    public function clearFilters(): void { $this->reset(['gender','categoryId','debtorStatus']); $this->page = 1; }

    public function export()
    {
        $this->exporting = true;
        try { $result = $this->render()->getData()['result']; } finally { $this->exporting = false; }
        $filename = str($this->report)->replace('_', '-')->append('-'.now()->format('Y-m-d').'.csv')->toString();

        return response()->streamDownload(function () use ($result) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, $result['columns']);
            foreach ($result['rows'] as $row) {
                fputcsv($output, collect($row)->map(fn ($value) => strip_tags((string) $value))->all());
            }
            if ($result['summaryLabel'] ?? null) {
                fputcsv($output, []);
                fputcsv($output, [$result['summaryLabel'], $result['summaryValue']]);
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function availableReports(): array
    {
        $user = Auth::user();
        $all = [
            'student_register'=>'Student register','new_admissions'=>'New admissions','parents'=>'Parent register','student_statement'=>'Student statements',
            'fee_demand'=>'Fee demand','fee_collection'=>'Fee collection','debtors'=>'Debtors & arrears','cash_pool'=>'Cash pool statement','expenses'=>'Expenses','payroll'=>'Payroll',
            'attendance'=>'Daily attendance','attendance_performance'=>'Attendance performance','staff_register'=>'Active staff','inactive_staff'=>'Inactive staff',
            'staff_attendance'=>'Staff attendance','staff_activity'=>'Staff activity','class_enrolment'=>'Class enrolment','subject_allocation'=>'Subject allocation',
            'academic_performance'=>'Subject performance','leave'=>'Leave report','term_history'=>'Term history','roles'=>'User roles','audit'=>'Audit trail','licence'=>'Licence status',
        ];
        if ($user->isSuperadmin() || $user->role === 'admin') return $all;
        if ($user->role === 'bursar' || strcasecmp($user->designation?->name ?? '', 'Bursar') === 0) {
            return collect($all)->only(['student_statement','fee_demand','fee_collection','debtors','cash_pool','expenses','payroll'])->all();
        }

        $keys = collect();
        if ($user->hasModuleAccess('students')) $keys->push('student_register','new_admissions');
        if ($user->hasModuleAccess('parents')) $keys->push('parents');
        if ($user->hasModuleAccess('finance')) $keys->push('student_statement','fee_demand','fee_collection','debtors','cash_pool','expenses');
        if ($user->hasPermission('staff.payroll')) $keys->push('payroll');
        if ($user->hasModuleAccess('attendance')) $keys->push('attendance','attendance_performance');
        if ($user->hasModuleAccess('staff')) $keys->push('staff_register','inactive_staff','staff_attendance','staff_activity','leave');
        if ($user->hasModuleAccess('academics') || $user->hasModuleAccess('exams')) $keys->push('class_enrolment','subject_allocation','academic_performance','term_history');
        if ($user->hasModuleAccess('settings')) $keys->push('roles','audit','licence');

        return collect($all)->only($keys->unique()->values())->all();
    }

    public function render()
    {
        $reportOptions = $this->availableReports();
        if (! array_key_exists($this->report, $reportOptions)) $this->report = array_key_first($reportOptions) ?? 'student_register';
        $school = Auth::user()->school;
        $term = Term::where('school_id', $school->id)->find($this->termId) ?? $school->currentTerm();
        $dateRange = fn ($query, string $column) => $query->when($this->dateFrom, fn ($q) => $q->whereDate($column, '>=', $this->dateFrom))->when($this->dateTo, fn ($q) => $q->whereDate($column, '<=', $this->dateTo));
        $result = match ($this->report) {
            'student_register' => $this->table(['Admission no', 'Learner', 'Class', 'Stream', 'Gender', 'Category', 'Status'], $this->filteredStudents($school,$term,['schoolClass','stream','category'])->map(fn($s)=>[$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',$s->stream?->name ?? '—',ucfirst($s->gender ?? '—'),$s->category?->name ?? '—',ucfirst($s->status)])),
            'new_admissions' => $this->table(['Admission date', 'Admission no', 'Learner', 'Class', 'Gender', 'Category', 'Guardian contact'], $this->filteredStudents($school,$term,['schoolClass','guardians','category'])->filter(fn($s)=>!$term||$s->admission_date?->year===$term->year)->sortByDesc('admission_date')->map(fn($s)=>[$s->admission_date?->format('d M Y') ?? '—',$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',ucfirst($s->gender ?? '—'),$s->category?->name ?? '—',$s->guardians->first()?->phone ?? '—'])),
            'fee_demand', 'student_statement' => $this->fees($school, $term),
            'fee_collection' => $this->table(['Date', 'Receipt', 'Learner', 'Method', 'Amount'], $dateRange(FeePayment::with('student')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'paid_at')->latest('paid_at')->get()->map(fn($p)=>[$p->paid_at?->format('d M Y'), '#'.$p->id, $p->student?->name ?? '—', ucfirst($p->method), number_format($p->amount,2)]), 'Amount collected', $dateRange(FeePayment::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'paid_at')->sum('amount')),
            'debtors' => $this->debtors($school, $term),
            'cash_pool' => $this->pool($school, $term, $dateRange),
            'expenses' => $this->table(['Date', 'Receipt / voucher', 'Category', 'Description', 'Amount'], $dateRange(Expense::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'expense_date')->latest('expense_date')->get()->map(fn($e)=>[$e->expense_date?->format('d M Y'),$e->reference_number ?? '-',$e->category,$e->description,number_format($e->amount,2)]), 'Expenses', $dateRange(Expense::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'expense_date')->sum('amount')),
            'payroll' => $this->table(['Paid date', 'Period', 'Staff', 'Amount'], PayrollRun::with('staff')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id))->latest('paid_at')->get()->map(fn($p)=>[$p->paid_at?->format('d M Y'),$p->period,$p->staff?->name ?? '—',number_format($p->amount,2)]), 'Payroll paid', PayrollRun::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id))->sum('amount')),
            'attendance' => $this->attendance($school, $term, $dateRange),
            'attendance_performance' => $this->attendancePerformance($school, $term, $dateRange),
            'staff_register', 'inactive_staff' => $this->staff($school, $this->report === 'staff_register' ? 'active' : 'inactive'),
            'staff_attendance' => $this->staffAttendance($school, $dateRange),
            'staff_activity' => $this->staffActivity($school, $dateRange),
            'parents' => $this->parents($school),
            'class_enrolment' => $this->classEnrolment($school),
            'subject_allocation' => $this->subjects($school, $term),
            'academic_performance' => $this->performance($school, $term),
            'leave' => $this->leave($school),
            'term_history' => $this->table(['Year', 'Term', 'Status', 'Current', 'Closed date'], Term::where('school_id',$school->id)->latest('year')->get()->map(fn($t)=>[$t->year,$t->name,ucfirst($t->status),$t->is_current?'Yes':'No',$t->closed_at?->format('d M Y') ?? '—'])),
            'roles' => $this->table(['Role', 'Accounts'], User::where('school_id',$school->id)->select('role',DB::raw('count(*) as total'))->groupBy('role')->get()->map(fn($u)=>[ucfirst($u->role),$u->total])),
            'audit' => $this->audit($school, $dateRange),
            'licence' => $this->licence($school),
            default => $this->table([], collect(), null, null, 'This report is not available.'),
        };
        $result['rows'] = collect($result['rows']);
        $totalRows = $result['rows']->count(); $lastPage = max(1, (int) ceil($totalRows / $this->perPage)); $this->page = min($this->page, $lastPage);
        if (! $this->exporting) $result['rows'] = $result['rows']->forPage($this->page, $this->perPage)->values();
        $result['pagination'] = ['total'=>$totalRows,'page'=>$this->page,'last_page'=>$lastPage,'from'=>$totalRows?($this->page-1)*$this->perPage+1:0,'to'=>min($this->page*$this->perPage,$totalRows)];
        return view('livewire.reports', ['term'=>$term,'terms'=>Term::where('school_id',$school->id)->latest('year')->get(),'categories'=>StudentCategory::where('school_id',$school->id)->orderBy('name')->get(),'result'=>$result,'reportOptions'=>$reportOptions,'pageTitle'=>'Reports']);
    }

    private function filteredStudents($school,$term,array $with=[]){$students=Student::with($with)->where('school_id',$school->id)->when($term,fn($q)=>$q->where(fn($scope)=>$scope->whereHas('enrolments',fn($e)=>$e->where('term_id',$term->id))->orWhere(fn($fallback)=>$fallback->whereDoesntHave('enrolments',fn($e)=>$e->where('term_id',$term->id))->where('term_id',$term->id))))->when($this->gender,fn($q)=>$q->where('gender',$this->gender))->when($this->categoryId,fn($q)=>$q->where(fn($scope)=>$scope->where('student_category_id',$this->categoryId)->orWhereHas('enrolments',fn($e)=>$e->where('term_id',$term?->id)->where('student_category_id',$this->categoryId))))->orderBy('name')->get();return $students->when($this->debtorStatus==='debtors',fn($rows)=>$rows->filter(fn($s)=>$s->balance($term)>0))->when($this->debtorStatus==='cleared',fn($rows)=>$rows->filter(fn($s)=>$s->balance($term)<=0))->values();}

    private function table(array $columns, $rows, ?string $summaryLabel = null, ?float $summaryValue = null, ?string $note = null): array { $rows=collect($rows);return compact('columns','rows','summaryLabel','summaryValue','note'); }
    private function fees($school,$term): array {$rows=$this->filteredStudents($school,$term,['schoolClass','category'])->where('status','active')->map(fn($s)=>[$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',$s->category?->name ?? '—',ucfirst($s->gender??'—'),number_format($s->totalDue($term),2),number_format($s->totalPaid($term),2),number_format($s->balance($term),2)]);return $this->table(['Admission no','Learner','Class','Category','Gender','Expected','Paid','Balance'],$rows,'Expected fees',$rows->sum(fn($r)=>(float)str_replace(',','',$r[5])));}
    private function debtors($school,$term): array {$rows=$this->filteredStudents($school,$term,['schoolClass','guardians','category'])->where('status','active')->map(fn($s)=>[$s->name,$s->schoolClass?->name ?? '—',$s->category?->name ?? '—',ucfirst($s->gender??'—'),$s->guardians->first()?->phone ?? '—',number_format($s->balance($term),2)])->filter(fn($r)=>(float)str_replace(',','',$r[5])>0);return $this->table(['Learner','Class','Category','Gender','Guardian contact','Amount due'],$rows,'Total arrears',$rows->sum(fn($r)=>(float)str_replace(',','',$r[5])));}
    private function pool($school,$term,$range): array {$entries=$range(CashPoolEntry::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'transacted_at')->latest('transacted_at')->get();$credits=$entries->where('direction','credit')->sum('amount');$debits=$entries->where('direction','debit')->sum('amount');return $this->table(['Date','Direction','Description','Amount'],$entries->map(fn($e)=>[$e->transacted_at?->format('d M Y'),ucfirst($e->direction),$e->description,number_format($e->amount,2)]),'Net pool balance',$credits-$debits);}
    private function attendance($school,$term,$range): array {$records=$range(AttendanceRecord::with('student')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'attendance_date')->latest('attendance_date')->get();return $this->table(['Date','Learner','Status'],$records->map(fn($r)=>[$r->attendance_date?->format('d M Y'),$r->student?->name ?? '—',ucfirst($r->status)]),'Present records',$records->where('status','present')->count());}
    private function attendancePerformance($school,$term,$range): array {$records=$range(AttendanceRecord::with('student.schoolClass')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'attendance_date')->get()->groupBy('student_id');$rows=$records->map(function($items){$student=$items->first()->student;$total=$items->count();$present=$items->whereIn('status',['present','late'])->count();return [$student?->name??'—',$student?->schoolClass?->name??'—',$total,$present,$items->where('status','absent')->count(),$total?round($present/$total*100,1).'%':'0%'];})->sortBy(fn($row)=>(float)$row[5])->values();return $this->table(['Learner','Class','Recorded days','Present / late','Absent','Attendance rate'],$rows,'Average attendance rate',$rows->count()?$rows->avg(fn($row)=>(float)$row[5]):0);}
    private function staff($school,$status): array {return $this->table(['Staff no','Name','Role','Phone','Job title'],User::where('school_id',$school->id)->where('employment_status',$status)->orderBy('name')->get()->map(fn($u)=>[$u->staff_number ?? '—',$u->name,ucfirst($u->role),$u->phone ?? '—',$u->job_title ?? '—']));}
    private function staffAttendance($school,$range): array {$records=$range(StaffAttendanceRecord::with(['staff:id,name,staff_number,job_title','recorder:id,name'])->where('school_id',$school->id),'attendance_date')->latest('attendance_date')->get();$rows=$records->map(fn($record)=>[$record->attendance_date?->format('d M Y'),$record->staff?->staff_number ?? '—',$record->staff?->name ?? 'Deleted staff',$record->staff?->job_title ?? '—',str($record->status)->replace('_',' ')->title()->toString(),$record->note ?? '—',$record->recorder?->name ?? 'System']);$present=$records->whereIn('status',['present','late'])->count();return $this->table(['Date','Staff no','Staff member','Job title','Status','Note','Recorded by'],$rows,'Present / late records',$present,'Staff attendance is based on saved daily attendance records.');}
    private function staffActivity($school,$range): array {$logs=$range(AuditLog::with('user')->where('school_id',$school->id)->whereNotNull('user_id')->whereHas('user',fn($query)=>$query->whereNotIn('role',['student','parent'])),'created_at')->latest()->get();$rows=$logs->map(fn($log)=>[$log->created_at?->format('d M Y H:i'),$log->user?->staff_number ?? '—',$log->user?->name ?? 'Deleted staff',str($log->event)->replace('.',' ')->title()->toString(),$log->subject_type ? class_basename($log->subject_type).($log->subject_id ? ' #'.$log->subject_id : '') : '—',$log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_SLASHES) : '—',$log->ip_address ?? '—']);return $this->table(['When','Staff no','Staff member','Activity','Record','Details','IP address'],$rows,'Recorded staff activities',$logs->count(),'Activity appears when an audited action is performed.');}
    private function parents($school): array {$rows=User::with('portalStudents.schoolClass')->where('school_id',$school->id)->where('role','parent')->orderBy('name')->get()->map(fn($parent)=>[$parent->name,$parent->email,$parent->phone ?? '—',$parent->portalStudents->map(fn($student)=>$student->name.' · '.($student->schoolClass?->name ?? '—'))->join('; ') ?: 'No learner linked']);return $this->table(['Parent / guardian','Email','Phone','Linked learners'],$rows,'Parent accounts',$rows->count());}
    private function classEnrolment($school): array {$rows=DB::table('school_classes')->leftJoin('students',function($join)use($school){$join->on('students.school_class_id','=','school_classes.id')->where('students.school_id',$school->id)->where('students.status','active');})->where('school_classes.school_id',$school->id)->select('school_classes.name',DB::raw('count(students.id) as total'))->groupBy('school_classes.id','school_classes.name')->orderBy('school_classes.name')->get()->map(fn($r)=>[$r->name,$r->total]);return $this->table(['Class','Active learners'],$rows,'Total active learners',$rows->sum(fn($r)=>$r[1]));}
    private function subjects($school,$term): array {$rows=DB::table('staff_subjects')->join('subjects','subjects.id','=','staff_subjects.subject_id')->leftJoin('school_classes','school_classes.id','=','staff_subjects.school_class_id')->join('users','users.id','=','staff_subjects.user_id')->where('staff_subjects.school_id',$school->id)->when($term,fn($q)=>$q->where('staff_subjects.term_id',$term->id))->orderBy('subjects.name')->get(['subjects.name as subject','school_classes.name as class','users.name as teacher'])->map(fn($r)=>[$r->subject,$r->class ?? 'All classes',$r->teacher]);return $this->table(['Subject','Class','Teacher'],$rows);}
    private function performance($school,$term): array {$rows=DB::table('exam_marks')->join('exam_papers','exam_papers.id','=','exam_marks.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->join('exam_paper_submissions','exam_paper_submissions.exam_paper_id','=','exam_papers.id')->join('subjects','subjects.id','=','exam_papers.subject_id')->where('exams.school_id',$school->id)->where('exam_paper_submissions.status','approved')->when($term,fn($q)=>$q->where('exams.term_id',$term->id))->select('subjects.name',DB::raw('round(avg(exam_marks.score / exam_papers.maximum_score * 100),2) as average'),DB::raw('count(exam_marks.id) as marks'))->groupBy('subjects.id','subjects.name')->orderByDesc('average')->get()->map(fn($r)=>[$r->name,$r->marks,$r->average.'%']);return $this->table(['Subject','Approved marks','Average'],$rows);}
    private function leave($school): array {$rows=DB::table('staff_leaves')->join('users','users.id','=','staff_leaves.user_id')->where('staff_leaves.school_id',$school->id)->latest('staff_leaves.id')->get(['users.name','staff_leaves.type','staff_leaves.starts_on','staff_leaves.ends_on','staff_leaves.status'])->map(fn($r)=>[$r->name,$r->type,$r->starts_on,$r->ends_on,ucfirst($r->status)]);return $this->table(['Staff','Leave type','Start','End','Status'],$rows);}
    private function audit($school,$range): array {$rows=$range(AuditLog::with('user')->where('school_id',$school->id),'created_at')->latest()->get()->map(fn($log)=>[$log->created_at?->format('d M Y H:i'),str_replace('.',' ',ucwords($log->event,'.')),$log->user?->name ?? 'System',$log->metadata ? json_encode($log->metadata) : '—']);return $this->table(['When','Action','By','Details'],$rows,null,null,'Audit entries are captured from the time this feature is enabled.');}
    private function licence($school): array {$status=$school->license_expires_at && $school->license_expires_at->isPast() ? 'expired' : $school->license_status;return $this->table(['School','Licence type','Plan','Status','Starts','Expires','Student limit'],[[$school->name,$school->is_demo?'Demo / trial':'Licensed school',ucfirst($school->license_plan),ucfirst($status),$school->license_started_at?->format('d M Y') ?? '—',$school->license_expires_at?->format('d M Y') ?? 'No expiry',$school->license_student_limit ?? 'No limit']],null,null,$school->is_demo?'This school is currently a demo/trial environment.':'This school is configured as a non-demo licensed institution.');}
}
