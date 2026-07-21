<?php

namespace App\Livewire;

use App\Models\AttendanceRecord;
use App\Models\AuditLog;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\PayrollRun;
use App\Models\Student;
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

    public function mount(): void
    {
        $term = Auth::user()->school->currentTerm();
        $this->termId = (string) ($term?->id ?? '');
        $this->dateFrom = now()->startOfMonth()->toDateString();
        $this->dateTo = now()->toDateString();
    }

    public function export()
    {
        $result = $this->render()->getData()['result'];
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

    public function render()
    {
        $school = Auth::user()->school;
        $term = Term::where('school_id', $school->id)->find($this->termId) ?? $school->currentTerm();
        $dateRange = fn ($query, string $column) => $query->when($this->dateFrom, fn ($q) => $q->whereDate($column, '>=', $this->dateFrom))->when($this->dateTo, fn ($q) => $q->whereDate($column, '<=', $this->dateTo));
        $result = match ($this->report) {
            'student_register' => $this->table(['Admission no', 'Learner', 'Class', 'Stream', 'Gender', 'Status'], Student::with(['schoolClass','stream'])->where('school_id',$school->id)->orderBy('name')->get()->map(fn($s)=>[$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',$s->stream?->name ?? '—',$s->gender ?? '—',ucfirst($s->status)])),
            'new_admissions' => $this->table(['Admission date', 'Admission no', 'Learner', 'Class', 'Guardian contact'], Student::with(['schoolClass','guardians'])->where('school_id',$school->id)->when($term,fn($q)=>$q->whereYear('admission_date',$term->year))->orderByDesc('admission_date')->get()->map(fn($s)=>[$s->admission_date?->format('d M Y') ?? '—',$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',$s->guardians->first()?->phone ?? '—'])),
            'fee_demand', 'student_statement' => $this->fees($school, $term),
            'fee_collection' => $this->table(['Date', 'Receipt', 'Learner', 'Method', 'Amount'], $dateRange(FeePayment::with('student')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'paid_at')->latest('paid_at')->get()->map(fn($p)=>[$p->paid_at?->format('d M Y'), '#'.$p->id, $p->student?->name ?? '—', ucfirst($p->method), number_format($p->amount,2)]), 'Amount collected', $dateRange(FeePayment::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'paid_at')->sum('amount')),
            'debtors' => $this->debtors($school, $term),
            'cash_pool' => $this->pool($school, $term, $dateRange),
            'expenses' => $this->table(['Date', 'Category', 'Description', 'Amount'], $dateRange(Expense::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'expense_date')->latest('expense_date')->get()->map(fn($e)=>[$e->expense_date?->format('d M Y'),$e->category,$e->description,number_format($e->amount,2)]), 'Expenses', $dateRange(Expense::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'expense_date')->sum('amount')),
            'payroll' => $this->table(['Paid date', 'Period', 'Staff', 'Amount'], PayrollRun::with('staff')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id))->latest('paid_at')->get()->map(fn($p)=>[$p->paid_at?->format('d M Y'),$p->period,$p->staff?->name ?? '—',number_format($p->amount,2)]), 'Payroll paid', PayrollRun::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id))->sum('amount')),
            'attendance' => $this->attendance($school, $term, $dateRange),
            'attendance_performance' => $this->attendancePerformance($school, $term, $dateRange),
            'staff_register', 'inactive_staff' => $this->staff($school, $this->report === 'staff_register' ? 'active' : 'inactive'),
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
        return view('livewire.reports', ['term'=>$term,'terms'=>Term::where('school_id',$school->id)->latest('year')->get(),'result'=>$result,'pageTitle'=>'Reports']);
    }

    private function table(array $columns, $rows, ?string $summaryLabel = null, ?float $summaryValue = null, ?string $note = null): array { return compact('columns','rows','summaryLabel','summaryValue','note'); }
    private function fees($school,$term): array {$rows=Student::with('schoolClass')->where('school_id',$school->id)->where('status','active')->get()->map(fn($s)=>[$s->admission_no,$s->name,$s->schoolClass?->name ?? '—',number_format($s->totalDue($term),2),number_format($s->totalPaid($term),2),number_format($s->balance($term),2)]);return $this->table(['Admission no','Learner','Class','Expected','Paid','Balance'],$rows,'Expected fees',$rows->sum(fn($r)=>(float)str_replace(',','',$r[3])));}
    private function debtors($school,$term): array {$rows=Student::with(['schoolClass','guardians'])->where('school_id',$school->id)->where('status','active')->get()->map(fn($s)=>[$s->name,$s->schoolClass?->name ?? '—',$s->guardians->first()?->phone ?? '—',number_format($s->balance($term),2)])->filter(fn($r)=>(float)str_replace(',','',$r[3])>0);return $this->table(['Learner','Class','Guardian contact','Amount due'],$rows,'Total arrears',$rows->sum(fn($r)=>(float)str_replace(',','',$r[3])));}
    private function pool($school,$term,$range): array {$entries=$range(CashPoolEntry::where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'transacted_at')->latest('transacted_at')->get();$credits=$entries->where('direction','credit')->sum('amount');$debits=$entries->where('direction','debit')->sum('amount');return $this->table(['Date','Direction','Description','Amount'],$entries->map(fn($e)=>[$e->transacted_at?->format('d M Y'),ucfirst($e->direction),$e->description,number_format($e->amount,2)]),'Net pool balance',$credits-$debits);}
    private function attendance($school,$term,$range): array {$records=$range(AttendanceRecord::with('student')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'attendance_date')->latest('attendance_date')->get();return $this->table(['Date','Learner','Status'],$records->map(fn($r)=>[$r->attendance_date?->format('d M Y'),$r->student?->name ?? '—',ucfirst($r->status)]),'Present records',$records->where('status','present')->count());}
    private function attendancePerformance($school,$term,$range): array {$records=$range(AttendanceRecord::with('student.schoolClass')->where('school_id',$school->id)->when($term,fn($q)=>$q->where('term_id',$term->id)),'attendance_date')->get()->groupBy('student_id');$rows=$records->map(function($items){$student=$items->first()->student;$total=$items->count();$present=$items->whereIn('status',['present','late'])->count();return [$student?->name??'—',$student?->schoolClass?->name??'—',$total,$present,$items->where('status','absent')->count(),$total?round($present/$total*100,1).'%':'0%'];})->sortBy(fn($row)=>(float)$row[5])->values();return $this->table(['Learner','Class','Recorded days','Present / late','Absent','Attendance rate'],$rows,'Average attendance rate',$rows->count()?$rows->avg(fn($row)=>(float)$row[5]):0);}
    private function staff($school,$status): array {return $this->table(['Staff no','Name','Role','Phone','Job title'],User::where('school_id',$school->id)->where('employment_status',$status)->orderBy('name')->get()->map(fn($u)=>[$u->staff_number ?? '—',$u->name,ucfirst($u->role),$u->phone ?? '—',$u->job_title ?? '—']));}
    private function parents($school): array {$rows=User::with('portalStudents.schoolClass')->where('school_id',$school->id)->where('role','parent')->orderBy('name')->get()->map(fn($parent)=>[$parent->name,$parent->email,$parent->phone ?? '—',$parent->portalStudents->map(fn($student)=>$student->name.' · '.($student->schoolClass?->name ?? '—'))->join('; ') ?: 'No learner linked']);return $this->table(['Parent / guardian','Email','Phone','Linked learners'],$rows,'Parent accounts',$rows->count());}
    private function classEnrolment($school): array {$rows=DB::table('school_classes')->leftJoin('students',function($join)use($school){$join->on('students.school_class_id','=','school_classes.id')->where('students.school_id',$school->id)->where('students.status','active');})->where('school_classes.school_id',$school->id)->select('school_classes.name',DB::raw('count(students.id) as total'))->groupBy('school_classes.id','school_classes.name')->orderBy('school_classes.name')->get()->map(fn($r)=>[$r->name,$r->total]);return $this->table(['Class','Active learners'],$rows,'Total active learners',$rows->sum(fn($r)=>$r[1]));}
    private function subjects($school,$term): array {$rows=DB::table('staff_subjects')->join('subjects','subjects.id','=','staff_subjects.subject_id')->leftJoin('school_classes','school_classes.id','=','staff_subjects.school_class_id')->join('users','users.id','=','staff_subjects.user_id')->where('staff_subjects.school_id',$school->id)->when($term,fn($q)=>$q->where('staff_subjects.term_id',$term->id))->orderBy('subjects.name')->get(['subjects.name as subject','school_classes.name as class','users.name as teacher'])->map(fn($r)=>[$r->subject,$r->class ?? 'All classes',$r->teacher]);return $this->table(['Subject','Class','Teacher'],$rows);}
    private function performance($school,$term): array {$rows=DB::table('exam_marks')->join('exam_papers','exam_papers.id','=','exam_marks.exam_paper_id')->join('exams','exams.id','=','exam_papers.exam_id')->join('exam_paper_submissions','exam_paper_submissions.exam_paper_id','=','exam_papers.id')->join('subjects','subjects.id','=','exam_papers.subject_id')->where('exams.school_id',$school->id)->where('exam_paper_submissions.status','approved')->when($term,fn($q)=>$q->where('exams.term_id',$term->id))->select('subjects.name',DB::raw('round(avg(exam_marks.score / exam_papers.maximum_score * 100),2) as average'),DB::raw('count(exam_marks.id) as marks'))->groupBy('subjects.id','subjects.name')->orderByDesc('average')->get()->map(fn($r)=>[$r->name,$r->marks,$r->average.'%']);return $this->table(['Subject','Approved marks','Average'],$rows);}
    private function leave($school): array {$rows=DB::table('staff_leaves')->join('users','users.id','=','staff_leaves.user_id')->where('staff_leaves.school_id',$school->id)->latest('staff_leaves.id')->get(['users.name','staff_leaves.type','staff_leaves.starts_on','staff_leaves.ends_on','staff_leaves.status'])->map(fn($r)=>[$r->name,$r->type,$r->starts_on,$r->ends_on,ucfirst($r->status)]);return $this->table(['Staff','Leave type','Start','End','Status'],$rows);}
    private function audit($school,$range): array {$rows=$range(AuditLog::with('user')->where('school_id',$school->id),'created_at')->latest()->get()->map(fn($log)=>[$log->created_at?->format('d M Y H:i'),str_replace('.',' ',ucwords($log->event,'.')),$log->user?->name ?? 'System',$log->metadata ? json_encode($log->metadata) : '—']);return $this->table(['When','Action','By','Details'],$rows,null,null,'Audit entries are captured from the time this feature is enabled.');}
    private function licence($school): array {$status=$school->license_expires_at && $school->license_expires_at->isPast() ? 'expired' : $school->license_status;return $this->table(['School','Licence type','Plan','Status','Starts','Expires','Student limit'],[[$school->name,$school->is_demo?'Demo / trial':'Licensed school',ucfirst($school->license_plan),ucfirst($status),$school->license_started_at?->format('d M Y') ?? '—',$school->license_expires_at?->format('d M Y') ?? 'No expiry',$school->license_student_limit ?? 'No limit']],null,null,$school->is_demo?'This school is currently a demo/trial environment.':'This school is configured as a non-demo licensed institution.');}
}
