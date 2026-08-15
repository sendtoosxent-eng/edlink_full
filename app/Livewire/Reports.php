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
use App\Models\ReportExport;
use App\Jobs\GenerateReportExport;
use App\Support\TeacherAcademicScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Reports extends Component
{
    public string $report = 'student_register';
    public string $schoolScope = '';
    public string $termId = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public string $gender = '';
    public string $categoryId = '';
    public string $debtorStatus = '';
    public int $page = 1;
    public int $perPage = 40;
    public bool $exporting = false;
    public ?int $exportId = null;
    private bool $databasePagination = false;
    private ?array $databasePaginationMeta = null;

    public function mount(): void
    {
        $this->schoolScope = (string) Auth::user()->school_id;
        $term = Auth::user()->school->currentTerm();
        $this->termId = (string) ($term?->id ?? '');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
        $this->report = array_key_first($this->availableReports()) ?? 'student_register';
    }

    public function updatedSchoolScope(): void
    {
        $user = Auth::user();
        if ($this->schoolScope === 'all') {
            abort_unless($user->canViewGroupDashboard(), 403);
            $this->termId = '';
        } else {
            $school = $user->schoolAccesses()->whereKey((int) $this->schoolScope)->firstOrFail();
            $this->termId = (string) ($school->currentTerm()?->id ?? '');
        }
        $this->page = 1;
    }
    public function updatedReport(string $report): void
    {
        if (! array_key_exists($report, $this->availableReports())) {
            $this->report = array_key_first($this->availableReports()) ?? 'student_register';
        }
        $this->page = 1;
    }

    public function updated($property): void { if (in_array($property, ['report','schoolScope','termId','dateFrom','dateTo','gender','categoryId','debtorStatus'], true)) $this->page = 1; }
    public function previousPage(): void { $this->page = max(1, $this->page - 1); }
    public function nextPage(): void { $this->page++; }
    public function goToPage(int $page): void { $this->page = max(1, $page); }
    public function clearFilters(): void { $this->reset(['gender','categoryId','debtorStatus']); $this->page = 1; }

    public function queueExport(): void
    {
        abort_unless(Auth::user()->hasPermission('reports.view') && array_key_exists($this->report, $this->availableReports()), 403);
        $filename = str($this->report)->replace('_', '-')->append('-'.now()->format('Y-m-d').'.csv')->toString();
        $export = ReportExport::create([
            'school_id'=>Auth::user()->school_id, 'user_id'=>Auth::id(), 'report'=>$this->report,
            'filters'=>collect(['report','schoolScope','termId','dateFrom','dateTo','gender','categoryId','debtorStatus'])->mapWithKeys(fn($key)=>[$key=>$this->{$key}])->all(),
            'status'=>'queued', 'disk'=>'local', 'filename'=>$filename,
        ]);
        $this->exportId = $export->id;
        GenerateReportExport::dispatch($export->id);
    }

    public function dismissExport(): void
    {
        $this->exportId = null;
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
        if (TeacherAcademicScope::isTeacher($user)) {
            return collect($all)->only([
                'attendance', 'attendance_performance', 'class_enrolment',
                'subject_allocation', 'academic_performance', 'term_history',
            ])->all();
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
        $user = Auth::user();
        $branchOptions = $user->schoolAccesses()->orderBy('name')->get();
        $allBranches = $this->schoolScope === 'all';
        if ($allBranches) {
            abort_unless($user->canViewGroupDashboard(), 403);
            $schools = $branchOptions->filter(fn ($branch) => $branch->school_group_id === $user->school->school_group_id)->values();
            $school = $user->school;
            $term = null;
        } else {
            $school = $branchOptions->firstWhere('id', (int) $this->schoolScope);
            abort_unless($school, 404);
            $schools = collect([$school]);
            $term = Term::where('school_id', $school->id)->find($this->termId) ?? $school->currentTerm();
        }
        $dateRange = fn ($query, string $column) => $query->when($this->dateFrom, fn ($q) => $q->whereDate($column, '>=', $this->dateFrom))->when($this->dateTo, fn ($q) => $q->whereDate($column, '<=', $this->dateTo));
        $this->databasePagination = ! $allBranches && ! $this->exporting;
        $this->databasePaginationMeta = null;
        $buildReport = function ($school, $term) use ($dateRange) {
            return match ($this->report) {
            'student_register' => $this->studentRegister($school, $term),
            'new_admissions' => $this->newAdmissions($school, $term),
            'fee_demand', 'student_statement' => $this->fees($school, $term),
            'fee_collection' => $this->table(['Date', 'Receipt', 'Learner', 'Method', 'Amount'], $this->pagedRows($dateRange(FeePayment::with('student')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'paid_at')->latest('paid_at'), fn($p)=>[$p->paid_at?->format('d M Y'), '#'.$p->id, $p->student?->name ?? '—', ucfirst($p->method), number_format($p->amount,2)]), 'Amount collected', $dateRange(FeePayment::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'paid_at')->sum('amount')),
            'debtors' => $this->debtors($school, $term),
            'cash_pool' => $this->pool($school, $term, $dateRange),
            'expenses' => $this->table(['Date', 'Receipt / voucher', 'Category', 'Description', 'Amount'], $this->pagedRows($dateRange(Expense::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'expense_date')->latest('expense_date'), fn($e)=>[$e->expense_date?->format('d M Y'),$e->reference_number ?? '-',$e->category,$e->description,number_format($e->amount,2)]), 'Expenses', $dateRange(Expense::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'expense_date')->sum('amount')),
            'payroll' => $this->table(['Paid date', 'Period', 'Staff', 'Amount'], $this->pagedRows(PayrollRun::with('staff')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id))->latest('paid_at'), fn($p)=>[$p->paid_at?->format('d M Y'),$p->period,$p->staff?->name ?? '—',number_format($p->amount,2)]), 'Payroll paid', PayrollRun::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id))->sum('amount')),
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
        };

        if ($allBranches) {
            $branchResults = $schools->map(function ($branch) use ($buildReport) {
                return ['school' => $branch, 'result' => $buildReport($branch, $branch->currentTerm())];
            });
            $firstResult = $branchResults->first()['result'] ?? $this->table([], collect());
            $result = $firstResult;
            $result['columns'] = array_merge(['Branch'], $firstResult['columns']);
            $result['rows'] = $branchResults->flatMap(function ($item) {
                $branchName = $item['school']->branch_name ?: $item['school']->name;
                return collect($item['result']['rows'])->map(fn ($row) => array_merge([$branchName], (array) $row));
            })->values();
            $summaries = $branchResults->pluck('result')->filter(fn ($item) => $item['summaryLabel'] ?? null);
            $result['summaryLabel'] = $summaries->first()['summaryLabel'] ?? null;
            $result['summaryValue'] = $summaries->sum(fn ($item) => (float) ($item['summaryValue'] ?? 0));
            $result['note'] = $firstResult['note'] ?? null;
        } else {
            $result = $buildReport($school, $term);
        }
        $result['rows'] = collect($result['rows']);
        if ($this->databasePaginationMeta) {
            $result['pagination'] = $this->databasePaginationMeta;
        } else {
            $totalRows = $result['rows']->count(); $lastPage = max(1, (int) ceil($totalRows / $this->perPage)); $this->page = min($this->page, $lastPage);
            if (! $this->exporting) $result['rows'] = $result['rows']->forPage($this->page, $this->perPage)->values();
            $result['pagination'] = ['total'=>$totalRows,'page'=>$this->page,'last_page'=>$lastPage,'from'=>$totalRows?($this->page-1)*$this->perPage+1:0,'to'=>min($this->page*$this->perPage,$totalRows)];
        }
        $queuedExport = $this->exportId ? ReportExport::where('user_id',Auth::id())->find($this->exportId) : null;
        return view('livewire.reports', ['term'=>$term,'terms'=>$allBranches ? collect() : Term::where('school_id',$school->id)->latest('year')->get(),'categories'=>StudentCategory::whereIn('school_id',$schools->pluck('id'))->orderBy('name')->get(),'result'=>$result,'reportOptions'=>$reportOptions,'branchOptions'=>$branchOptions,'allBranches'=>$allBranches,'queuedExport'=>$queuedExport,'pageTitle'=>'Reports']);
    }

    private function studentQuery($school,$term,array $with=[]){$financialRelations=$term?['enrolments'=>fn($q)=>$q->where('term_id',$term->id),'arrears'=>fn($q)=>$q->where('applied_term_id',$term->id),'postedFeePayments'=>fn($q)=>$q->where('term_id',$term->id)]:[];$query=Student::with(array_merge($with,$financialRelations))->where('students.school_id',$school->id)->when($term,fn($q)=>$q->where(fn($scope)=>$scope->whereHas('enrolments',fn($e)=>$e->where('term_id',$term->id))->orWhere(fn($fallback)=>$fallback->whereDoesntHave('enrolments',fn($e)=>$e->where('term_id',$term->id))->where('students.term_id',$term->id))))->when($this->gender,fn($q)=>$q->where('gender',$this->gender))->when($this->categoryId,fn($q)=>$q->where(fn($scope)=>$scope->where('student_category_id',$this->categoryId)->orWhereHas('enrolments',fn($e)=>$e->where('term_id',$term?->id)->where('student_category_id',$this->categoryId))));return TeacherAcademicScope::scopeStudents($query,Auth::user(),$term?->id);}
    private function filteredStudents($school,$term,array $with=[]){$students=$this->studentQuery($school,$term,$with)->orderBy('name')->get();return $students->when($this->debtorStatus==='debtors',fn($rows)=>$rows->filter(fn($s)=>$s->balance($term)>0))->when($this->debtorStatus==='cleared',fn($rows)=>$rows->filter(fn($s)=>$s->balance($term)<=0))->values();}

    private function studentRegister($school,$term): array
    {
        $mapper=fn($s)=>[$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',$s->stream?->name ?? '—',ucfirst($s->gender ?? '—'),$s->category?->name ?? '—',ucfirst($s->status)];
        $rows=$this->debtorStatus ? $this->filteredStudents($school,$term,['schoolClass','stream','category'])->map($mapper) : $this->pagedRows($this->studentQuery($school,$term,['schoolClass','stream','category'])->orderBy('name'),$mapper);
        return $this->table(['Admission no','Learner','Class','Stream','Gender','Category','Status'],$rows);
    }

    private function newAdmissions($school,$term): array
    {
        $mapper=fn($s)=>[$s->admission_date?->format('d M Y') ?? '—',$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',ucfirst($s->gender ?? '—'),$s->category?->name ?? '—',$s->guardians->first()?->phone ?? '—'];
        $query=$this->studentQuery($school,$term,['schoolClass','guardians','category'])->when($term,fn($q)=>$q->whereYear('admission_date',$term->year))->latest('admission_date');
        $rows=$this->debtorStatus ? $this->filteredStudents($school,$term,['schoolClass','guardians','category'])->filter(fn($s)=>!$term||$s->admission_date?->year===$term->year)->sortByDesc('admission_date')->map($mapper) : $this->pagedRows($query,$mapper);
        return $this->table(['Admission date','Admission no','Learner','Class','Gender','Category','Guardian contact'],$rows);
    }

    private function table(array $columns, $rows, ?string $summaryLabel = null, ?float $summaryValue = null, ?string $note = null): array { $rows=collect($rows);return compact('columns','rows','summaryLabel','summaryValue','note'); }
    private function pagedRows($query, callable $mapper)
    {
        if (! $this->databasePagination) return $query->get()->map($mapper);
        $total = (clone $query)->reorder()->count();
        $lastPage = max(1, (int) ceil($total / $this->perPage));
        $this->page = min($this->page, $lastPage);
        $rows = $query->forPage($this->page, $this->perPage)->get()->map($mapper)->values();
        $this->databasePaginationMeta = ['total'=>$total,'page'=>$this->page,'last_page'=>$lastPage,'from'=>$total?($this->page-1)*$this->perPage+1:0,'to'=>min($this->page*$this->perPage,$total)];
        return $rows;
    }
    private function fees($school,$term): array {$rows=$this->filteredStudents($school,$term,['schoolClass','category'])->where('status','active')->map(fn($s)=>[$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',$s->category?->name ?? '—',ucfirst($s->gender??'—'),number_format($s->totalDue($term),2),number_format($s->totalPaid($term),2),number_format($s->balance($term),2)]);return $this->table(['Admission no','Learner','Class','Category','Gender','Expected','Paid','Balance'],$rows,'Expected fees',$rows->sum(fn($r)=>(float)str_replace(',','',$r[5])));}
    private function debtors($school,$term): array {$rows=$this->filteredStudents($school,$term,['schoolClass','guardians','category'])->where('status','active')->map(fn($s)=>[$s->name,$s->schoolClass?->name ?? '—',$s->category?->name ?? '—',ucfirst($s->gender??'—'),$s->guardians->first()?->phone ?? '—',number_format($s->balance($term),2)])->filter(fn($r)=>(float)str_replace(',','',$r[5])>0);return $this->table(['Learner','Class','Category','Gender','Guardian contact','Amount due'],$rows,'Total arrears',$rows->sum(fn($r)=>(float)str_replace(',','',$r[5])));}
    private function pool($school,$term,$range): array {$base=$range(CashPoolEntry::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'transacted_at');$balance=(clone $base)->selectRaw("SUM(CASE WHEN direction = 'credit' THEN amount ELSE -amount END) as balance")->value('balance')??0;$rows=$this->pagedRows((clone $base)->latest('transacted_at'),fn($e)=>[$e->transacted_at?->format('d M Y'),ucfirst($e->direction),$e->description,number_format($e->amount,2)]);return $this->table(['Date','Direction','Description','Amount'],$rows,'Net pool balance',(float)$balance);}
    private function attendance($school,$term,$range): array {$query=AttendanceRecord::with('student')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id));$base=$range(TeacherAcademicScope::scopeAttendance($query,Auth::user(),$term?->id),'attendance_date');$present=(clone $base)->where('status','present')->count();$rows=$this->pagedRows((clone $base)->latest('attendance_date'),fn($r)=>[$r->attendance_date?->format('d M Y'),$r->student?->name ?? '—',ucfirst($r->status)]);return $this->table(['Date','Learner','Status'],$rows,'Present records',$present);}
    private function attendancePerformance($school,$term,$range): array {$query=AttendanceRecord::with('student.schoolClass')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id));$records=$range(TeacherAcademicScope::scopeAttendance($query,Auth::user(),$term?->id),'attendance_date')->get()->groupBy('student_id');$rows=$records->map(function($items){$student=$items->first()->student;$total=$items->count();$present=$items->whereIn('status',['present','late'])->count();return [$student?->name??'—',$student?->schoolClass?->name??'—',$total,$present,$items->where('status','absent')->count(),$total?round($present/$total*100,1).'%':'0%'];})->sortBy(fn($row)=>(float)$row[5])->values();return $this->table(['Learner','Class','Recorded days','Present / late','Absent','Attendance rate'],$rows,'Average attendance rate',$rows->count()?$rows->avg(fn($row)=>(float)$row[5]):0);}
    private function staff($school,$status): array {return $this->table(['Staff no','Name','Role','Phone','Job title'],$this->pagedRows(User::where('school_id',$school->id)->where('employment_status',$status)->orderBy('name'),fn($u)=>[$u->staff_number ?? '—',$u->name,ucfirst($u->role),$u->phone ?? '—',$u->job_title ?? '—']));}
    private function staffAttendance($school,$range): array {$records=$range(StaffAttendanceRecord::with(['staff:id,name,staff_number,job_title','recorder:id,name'])->where('school_id',$school->id),'attendance_date')->latest('attendance_date')->get();$rows=$records->map(fn($record)=>[$record->attendance_date?->format('d M Y'),$record->staff?->staff_number ?? '—',$record->staff?->name ?? 'Deleted staff',$record->staff?->job_title ?? '—',str($record->status)->replace('_',' ')->title()->toString(),$record->note ?? '—',$record->recorder?->name ?? 'System']);$present=$records->whereIn('status',['present','late'])->count();return $this->table(['Date','Staff no','Staff member','Job title','Status','Note','Recorded by'],$rows,'Present / late records',$present,'Staff attendance is based on saved daily attendance records.');}
    private function staffActivity($school,$range): array {$logs=$range(AuditLog::with('user')->where('school_id',$school->id)->whereNotNull('user_id')->whereHas('user',fn($query)=>$query->whereNotIn('role',['student','parent'])),'created_at')->latest()->get();$rows=$logs->map(fn($log)=>[$log->created_at?->format('d M Y H:i'),$log->user?->staff_number ?? '—',$log->user?->name ?? 'Deleted staff',str($log->event)->replace('.',' ')->title()->toString(),$log->subject_type ? class_basename($log->subject_type).($log->subject_id ? ' #'.$log->subject_id : '') : '—',$log->metadata ? json_encode($log->metadata, JSON_UNESCAPED_SLASHES) : '—',$log->ip_address ?? '—']);return $this->table(['When','Staff no','Staff member','Activity','Record','Details','IP address'],$rows,'Recorded staff activities',$logs->count(),'Activity appears when an audited action is performed.');}
    private function parents($school): array {$rows=User::with('portalStudents.schoolClass')->where('school_id',$school->id)->where('role','parent')->orderBy('name')->get()->map(fn($parent)=>[$parent->name,$parent->email,$parent->phone ?? '—',$parent->portalStudents->map(fn($student)=>$student->name.' · '.($student->schoolClass?->name ?? '—'))->join('; ') ?: 'No learner linked']);return $this->table(['Parent / guardian','Email','Phone','Linked learners'],$rows,'Parent accounts',$rows->count());}
    private function classEnrolment($school): array {$user=Auth::user();$classIds=TeacherAcademicScope::isTeacher($user)?TeacherAcademicScope::academicClassIds($user,$school->currentTerm()?->id):null;$query=DB::table('school_classes')->leftJoin('students',function($join)use($school){$join->on('students.school_class_id','=','school_classes.id')->where('students.school_id',$school->id)->where('students.status','active');})->where('school_classes.school_id',$school->id)->when($classIds,fn($q)=>$q->whereIn('school_classes.id',$classIds));$rows=$query->select('school_classes.name',DB::raw('count(students.id) as total'))->groupBy('school_classes.id','school_classes.name')->orderBy('school_classes.name')->get()->map(fn($r)=>[$r->name,$r->total]);return $this->table(['Class','Active learners'],$rows,'Total active learners',$rows->sum(fn($r)=>$r[1]));}
    private function subjects($school,$term): array {$user=Auth::user();$rows=DB::table('staff_subjects')->join('subjects','subjects.id','=','staff_subjects.subject_id')->leftJoin('school_classes','school_classes.id','=','staff_subjects.school_class_id')->join('users','users.id','=','staff_subjects.user_id')->where('staff_subjects.school_id',$school->id)->when($term,fn($q)=>$q->where(fn($scope)=>$scope->where('staff_subjects.term_id',$term->id)->orWhereNull('staff_subjects.term_id')))->when(TeacherAcademicScope::isTeacher($user),fn($q)=>$q->where('staff_subjects.user_id',$user->id))->orderBy('subjects.name')->get(['subjects.name as subject','school_classes.name as class','users.name as teacher'])->map(fn($r)=>[$r->subject,$r->class ?? 'All classes',$r->teacher]);return $this->table(['Subject','Class','Teacher'],$rows);}
    private function performance($school,$term): array {$user=Auth::user();$assignments=TeacherAcademicScope::isTeacher($user)?TeacherAcademicScope::subjectAssignments($user,$term?->id):collect();$query=DB::table('exam_marks')->join('exam_papers','exam_papers.id','=','exam_marks.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->join('exam_paper_submissions','exam_paper_submissions.exam_paper_id','=','exam_papers.id')->join('subjects','subjects.id','=','exam_papers.subject_id')->where('exams.school_id',$school->id)->where('exam_paper_submissions.status','approved')->when($term,fn($q)=>$q->where('exams.term_id',$term->id));if(TeacherAcademicScope::isTeacher($user)){$query->where(function($allowed)use($assignments){foreach($assignments->values() as $index=>$assignment){$method=$index===0?'where':'orWhere';$allowed->{$method}(fn($pair)=>$pair->where('exams.school_class_id',$assignment->school_class_id)->where('exam_papers.subject_id',$assignment->subject_id));}if($assignments->isEmpty())$allowed->whereRaw('1 = 0');});}$rows=$query->select('subjects.name',DB::raw('round(avg(exam_marks.score / exam_papers.maximum_score * 100),2) as average'),DB::raw('count(exam_marks.id) as marks'))->groupBy('subjects.id','subjects.name')->orderByDesc('average')->get()->map(fn($r)=>[$r->name,$r->marks,$r->average.'%']);return $this->table(['Subject','Approved marks','Average'],$rows);}
    private function leave($school): array {$rows=DB::table('staff_leaves')->join('users','users.id','=','staff_leaves.user_id')->where('staff_leaves.school_id',$school->id)->latest('staff_leaves.id')->get(['users.name','staff_leaves.type','staff_leaves.starts_on','staff_leaves.ends_on','staff_leaves.status'])->map(fn($r)=>[$r->name,$r->type,$r->starts_on,$r->ends_on,ucfirst($r->status)]);return $this->table(['Staff','Leave type','Start','End','Status'],$rows);}
    private function audit($school,$range): array {$rows=$this->pagedRows($range(AuditLog::with('user')->where('school_id',$school->id),'created_at')->latest(),fn($log)=>[$log->created_at?->format('d M Y H:i'),str_replace('.',' ',ucwords($log->event,'.')),$log->user?->name ?? 'System',$log->metadata ? json_encode($log->metadata) : '—']);return $this->table(['When','Action','By','Details'],$rows,null,null,'Audit entries are captured from the time this feature is enabled.');}
    private function licence($school): array {$status=$school->license_expires_at && $school->license_expires_at->isPast() ? 'expired' : $school->license_status;return $this->table(['School','Licence type','Plan','Status','Starts','Expires','Student limit'],[[$school->name,$school->is_demo?'Demo / trial':'Licensed school',ucfirst($school->license_plan),ucfirst($status),$school->license_started_at?->format('d M Y') ?? '—',$school->license_expires_at?->format('d M Y') ?? 'No expiry',$school->license_student_limit ?? 'No limit']],null,null,$school->is_demo?'This school is currently a demo/trial environment.':'This school is configured as a non-demo licensed institution.');}
}
