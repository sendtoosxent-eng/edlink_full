<div class="space-y-6 pb-16">
    <script src="{{ asset('js/chart.umd.js') }}"></script>
@include('partials.chart-fonts')
    @php
        $quickActions = [];
        $quickActionBadge = 'Staff tools';

        if ($isFinanceWorkspace) {
            $quickActionBadge = 'Bursar tools';
            $financeActions = [
                ['permission' => 'finance.payments', 'label' => 'Record Payment', 'description' => 'Post fees and issue a receipt', 'route' => 'fee-payments.index', 'icon' => 'heroicon-o-banknotes', 'iconClass' => 'bg-emerald-100 text-emerald-700', 'hoverClass' => 'hover:border-emerald-300'],
                ['permission' => 'finance.adjustments', 'label' => 'Fee Adjustments', 'description' => 'Review discounts and corrections', 'route' => 'fee-adjustments.index', 'icon' => 'heroicon-o-calculator', 'iconClass' => 'bg-amber-100 text-amber-700', 'hoverClass' => 'hover:border-amber-300'],
                ['permission' => 'finance.expenses', 'label' => 'Record Expense', 'description' => 'Capture school expenditure', 'route' => 'expenses.index', 'icon' => 'heroicon-o-receipt-percent', 'iconClass' => 'bg-rose-100 text-rose-700', 'hoverClass' => 'hover:border-rose-300'],
                ['permission' => 'accounting.dashboard.view', 'label' => 'Accounting', 'description' => 'Open journals and ledgers', 'route' => 'accounting.index', 'icon' => 'heroicon-o-building-library', 'iconClass' => 'bg-indigo-100 text-indigo-700', 'hoverClass' => 'hover:border-indigo-300'],
                ['permission' => 'accounting.reconciliations.manage', 'label' => 'Reconcile Accounts', 'description' => 'Match cash and bank balances', 'route' => 'accounting.reconciliations', 'icon' => 'heroicon-o-scale', 'iconClass' => 'bg-cyan-100 text-cyan-700', 'hoverClass' => 'hover:border-cyan-300'],
                ['permission' => 'accounting.assets.view', 'label' => 'Fixed Assets', 'description' => 'Review the school asset register', 'route' => 'accounting.assets', 'icon' => 'heroicon-o-archive-box', 'iconClass' => 'bg-blue-100 text-blue-700', 'hoverClass' => 'hover:border-blue-300'],
                ['permission' => 'staff.payroll', 'label' => 'Run Payroll', 'description' => 'Prepare staff salary records', 'route' => 'payroll.index', 'icon' => 'heroicon-o-credit-card', 'iconClass' => 'bg-violet-100 text-violet-700', 'hoverClass' => 'hover:border-violet-300'],
                ['permission' => 'reports.view', 'label' => 'Finance Reports', 'description' => 'Review collections and spending', 'route' => 'reports.index', 'icon' => 'heroicon-o-chart-bar-square', 'iconClass' => 'bg-teal-100 text-teal-700', 'hoverClass' => 'hover:border-teal-300'],
                ['permission' => 'students.view', 'label' => 'Find Student', 'description' => 'Open a learner fee account', 'route' => 'students.index', 'icon' => 'heroicon-o-magnifying-glass', 'iconClass' => 'bg-orange-100 text-orange-700', 'hoverClass' => 'hover:border-orange-300'],
            ];
            $quickActions = collect($financeActions)->filter(fn ($action) => auth()->user()->hasPermission($action['permission']))
                ->map(fn ($action) => $action + ['url' => route($action['route'])])->take(10)->values()->all();
        } elseif ($isTeacherWorkspace) {
            $quickActionBadge = 'Teacher tools';
            $quickActions[] = ['label' => 'Homework', 'description' => 'Create or review class work', 'url' => route('homework.index'), 'icon' => 'heroicon-o-book-open', 'iconClass' => 'bg-amber-100 text-amber-700', 'hoverClass' => 'hover:border-amber-300'];
            if (auth()->user()->hasPermission('attendance.daily')) $quickActions[] = ['label' => 'Class Attendance', 'description' => 'Mark today\'s class register', 'url' => route('attendance.index'), 'icon' => 'heroicon-o-clipboard-document-check', 'iconClass' => 'bg-emerald-100 text-emerald-700', 'hoverClass' => 'hover:border-emerald-300'];
            if (auth()->user()->hasPermission('attendance.subject')) $quickActions[] = ['label' => 'Subject Attendance', 'description' => 'Record attendance for a lesson', 'url' => route('attendance.subject'), 'icon' => 'heroicon-o-clipboard-document-list', 'iconClass' => 'bg-cyan-100 text-cyan-700', 'hoverClass' => 'hover:border-cyan-300'];
            if (auth()->user()->hasPermission('exams.marks') || auth()->user()->hasPermission('exams.results')) $quickActions[] = ['label' => 'Enter Marks', 'description' => 'Open assigned mark sheets', 'url' => route('exams.marks'), 'icon' => 'heroicon-o-pencil-square', 'iconClass' => 'bg-violet-100 text-violet-700', 'hoverClass' => 'hover:border-violet-300'];
            if (auth()->user()->hasPermission('exams.results')) $quickActions[] = ['label' => 'View Results', 'description' => 'Review learner performance', 'url' => route('exams.results'), 'icon' => 'heroicon-o-chart-bar-square', 'iconClass' => 'bg-blue-100 text-blue-700', 'hoverClass' => 'hover:border-blue-300'];
            if (auth()->user()->hasPermission('academics.timetable')) $quickActions[] = ['label' => 'Timetable', 'description' => 'See lessons and teaching times', 'url' => route('timetable.index'), 'icon' => 'heroicon-o-calendar-days', 'iconClass' => 'bg-indigo-100 text-indigo-700', 'hoverClass' => 'hover:border-indigo-300'];
            if (\App\Support\TeacherAcademicScope::canViewStudentDirectory(auth()->user())) $quickActions[] = ['label' => 'Student Profile', 'description' => 'Choose one of your learners to view their profile', 'url' => route('students.index'), 'icon' => 'heroicon-o-identification', 'iconClass' => 'bg-orange-100 text-orange-700', 'hoverClass' => 'hover:border-orange-300'];
            if (auth()->user()->hasPermission('parents.communications')) $quickActions[] = ['label' => 'Message Parents', 'description' => 'Send a class communication', 'url' => route('communications.index'), 'icon' => 'heroicon-o-megaphone', 'iconClass' => 'bg-rose-100 text-rose-700', 'hoverClass' => 'hover:border-rose-300'];
            if (auth()->user()->hasPermission('reports.view')) $quickActions[] = ['label' => 'Teaching Reports', 'description' => 'Open academic reports', 'url' => route('reports.index'), 'icon' => 'heroicon-o-document-chart-bar', 'iconClass' => 'bg-teal-100 text-teal-700', 'hoverClass' => 'hover:border-teal-300'];
            $quickActions[] = ['label' => 'My Leave', 'description' => 'View or request staff leave', 'url' => route('leaves.index'), 'icon' => 'heroicon-o-clock', 'iconClass' => 'bg-slate-100 text-slate-700', 'hoverClass' => 'hover:border-slate-400'];
            $quickActions = array_slice($quickActions, 0, 10);
        } else {
            $staff = auth()->user();
            if ($staff->hasPermission('students.view')) $quickActions[] = ['label' => 'Students', 'description' => 'Open the learner directory', 'url' => route('students.index'), 'icon' => 'heroicon-o-academic-cap', 'iconClass' => 'bg-amber-100 text-amber-700'];
            if ($staff->hasPermission('finance.payments') || $staff->hasPermission('finance.adjustments')) $quickActions[] = ['label' => 'Fee Payments', 'description' => 'Open learner fee accounts', 'url' => route('fee-payments.index'), 'icon' => 'heroicon-o-banknotes', 'iconClass' => 'bg-emerald-100 text-emerald-700'];
            elseif ($staff->hasPermission('finance.expenses')) $quickActions[] = ['label' => 'Expenses', 'description' => 'Open school expenditure', 'url' => route('expenses.index'), 'icon' => 'heroicon-o-receipt-percent', 'iconClass' => 'bg-rose-100 text-rose-700'];
            if ($staff->hasPermission('attendance.daily')) $quickActions[] = ['label' => 'Daily Attendance', 'description' => 'Open today\'s learner register', 'url' => route('attendance.index'), 'icon' => 'heroicon-o-clipboard-document-check', 'iconClass' => 'bg-cyan-100 text-cyan-700'];
            elseif ($staff->hasPermission('attendance.subject')) $quickActions[] = ['label' => 'Subject Attendance', 'description' => 'Record lesson attendance', 'url' => route('attendance.subject'), 'icon' => 'heroicon-o-clipboard-document-list', 'iconClass' => 'bg-cyan-100 text-cyan-700'];
            if ($staff->hasPermission('academics.classes')) $quickActions[] = ['label' => 'Classes & Streams', 'description' => 'Open class organisation', 'url' => route('classes.index'), 'icon' => 'heroicon-o-rectangle-stack', 'iconClass' => 'bg-indigo-100 text-indigo-700'];
            elseif ($staff->hasPermission('academics.subjects')) $quickActions[] = ['label' => 'Subjects', 'description' => 'Open subject allocations', 'url' => route('subjects.index'), 'icon' => 'heroicon-o-book-open', 'iconClass' => 'bg-indigo-100 text-indigo-700'];
            elseif ($staff->hasPermission('academics.timetable')) $quickActions[] = ['label' => 'Timetable', 'description' => 'Open the school timetable', 'url' => route('timetable.index'), 'icon' => 'heroicon-o-calendar-days', 'iconClass' => 'bg-indigo-100 text-indigo-700'];
            if ($staff->hasPermission('exams.marks') || $staff->hasPermission('exams.results')) $quickActions[] = ['label' => 'Exams & Results', 'description' => 'Open marks and results', 'url' => route('exams.marks'), 'icon' => 'heroicon-o-pencil-square', 'iconClass' => 'bg-violet-100 text-violet-700'];
            elseif ($staff->hasPermission('exams.setup')) $quickActions[] = ['label' => 'Exam Setup', 'description' => 'Create and configure exams', 'url' => route('exams.setup'), 'icon' => 'heroicon-o-pencil-square', 'iconClass' => 'bg-violet-100 text-violet-700'];
            if ($staff->hasPermission('staff.directory')) $quickActions[] = ['label' => 'Staff Directory', 'description' => 'Find a staff member', 'url' => route('staff.index'), 'icon' => 'heroicon-o-user-group', 'iconClass' => 'bg-blue-100 text-blue-700'];
            elseif ($staff->hasPermission('staff.payroll')) $quickActions[] = ['label' => 'Payroll', 'description' => 'Open staff salary records', 'url' => route('payroll.index'), 'icon' => 'heroicon-o-credit-card', 'iconClass' => 'bg-blue-100 text-blue-700'];
            if ($staff->hasPermission('parents.communications')) $quickActions[] = ['label' => 'Communications', 'description' => 'Message the school community', 'url' => route('communications.index'), 'icon' => 'heroicon-o-megaphone', 'iconClass' => 'bg-orange-100 text-orange-700'];
            elseif ($staff->hasPermission('parents.manage')) $quickActions[] = ['label' => 'Parents', 'description' => 'Open parent accounts', 'url' => route('parents.index'), 'icon' => 'heroicon-o-user-group', 'iconClass' => 'bg-orange-100 text-orange-700'];
            if ($staff->hasPermission('reports.view')) $quickActions[] = ['label' => 'Reports', 'description' => 'Open permitted school reports', 'url' => route('reports.index'), 'icon' => 'heroicon-o-chart-bar-square', 'iconClass' => 'bg-teal-100 text-teal-700'];
            if ($staff->hasPermission('settings.manage')) $quickActions[] = ['label' => 'Settings', 'description' => 'Open school configuration', 'url' => route('settings.index'), 'icon' => 'heroicon-o-cog-6-tooth', 'iconClass' => 'bg-slate-100 text-slate-700'];
            $quickActions = array_slice($quickActions, 0, 9);
            $quickActions[] = ['label' => 'My Leave', 'description' => 'View or request staff leave', 'url' => route('leaves.index'), 'icon' => 'heroicon-o-clock', 'iconClass' => 'bg-slate-100 text-slate-700', 'hoverClass' => 'hover:border-slate-400'];
        }
    @endphp
    <section class="relative overflow-hidden rounded-2xl p-6 shadow-lg ring-2 ring-yellow-400/20 lg:p-8" style="background: linear-gradient(135deg, #252641 0%, #3a3d6b 100%);">
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-5"><div><h1 class="text-2xl font-bold tracking-tight text-amber-300">Welcome back, {{ explode(' ', auth()->user()->name)[0] }} &#128075;</h1><p class="text-sm text-slate-400 mt-2 leading-relaxed">Here's what's happening at {{ $school->name }} today.</p><p class="mt-3 text-xs font-medium text-yellow-400">{{ now()->format('l, d F Y · h:i A') }} · {{ $isFinanceWorkspace ? 'Finance Dashboard' : ($isTeacherWorkspace ? 'Teacher Dashboard' : (auth()->user()->designation?->name ?? ucfirst(auth()->user()->role))) }}</p></div>@if($isFinanceWorkspace)<a wire:navigate href="{{ route('reports.index') }}" class="rounded-xl bg-yellow-400 px-4 py-2.5 text-sm font-bold text-slate-950 shadow-sm">Open financial reports</a>@endif</div>
        <div class="absolute -bottom-10 -right-6 h-40 w-40 rounded-full bg-yellow-400/10"></div><div class="absolute -top-10 right-16 h-24 w-24 rounded-full bg-yellow-400/10"></div>
    </section>

    @if($isFinanceWorkspace)
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach([
                ['Expected fees',$finance['expected'],'Total billed plus arrears'],
                ['Income collected',$finance['income'],'Payments received this term'],
                ['Expenditure',$finance['expenses'],'Expenses recorded this term'],
                ['Outstanding',$finance['outstanding'],'Expected less collections'],
                ['Cash pool',$finance['poolBalance'],'Credits less debits'],
                ['Net cash flow',$finance['net'],'Income less expenditure'],
            ] as [$label,$value,$note])
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-2xl font-bold text-slate-900">UGX {{ number_format($value, 0) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $note }}</p>
                </section>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm xl:col-span-2">
                <h2 class="font-bold text-slate-900">Income and expenditure</h2>
                <p class="text-xs text-slate-500">Monthly accounting movement for the last six months.</p>
                <div class="mt-4 h-80"><canvas id="bursarCashFlowChart"></canvas></div>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-900">Current term position</h2>
                <div class="mt-4 h-80"><canvas id="bursarPositionChart"></canvas></div>
            </section>
        </div>

        <div class="grid gap-5 lg:grid-cols-2">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b p-5"><h2 class="font-bold">Recent income</h2></div>
                <div class="divide-y">@forelse($finance['recentPayments'] as $payment)<div class="flex items-center justify-between gap-3 p-4 text-sm"><div><p class="font-semibold">{{ $payment->student?->name ?? 'Unknown learner' }}</p><p class="text-xs text-slate-400">{{ $payment->paid_at?->format('d M Y') }} · {{ ucfirst($payment->method) }}</p></div><b class="text-emerald-700">UGX {{ number_format($payment->amount) }}</b></div>@empty<div class="p-8 text-center text-sm text-slate-400">No payments recorded.</div>@endforelse</div>
            </section>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b p-5"><h2 class="font-bold">Recent expenditure</h2></div>
                <div class="divide-y">@forelse($finance['recentExpenses'] as $expense)<div class="flex items-center justify-between gap-3 p-4 text-sm"><div><p class="font-semibold">{{ $expense->category }}</p><p class="text-xs text-slate-400">{{ $expense->expense_date?->format('d M Y') }} · {{ $expense->reference_number ?: 'No reference' }}</p></div><b class="text-rose-700">UGX {{ number_format($expense->amount) }}</b></div>@empty<div class="p-8 text-center text-sm text-slate-400">No expenses recorded.</div>@endforelse</div>
            </section>
        </div>

        <script>
            (() => {
                const draw = () => {
                    if (typeof Chart === 'undefined') return;
                    const flow = document.getElementById('bursarCashFlowChart');
                    if (flow && !flow.dataset.ready) {
                        flow.dataset.ready = '1';
                        new Chart(flow, {type:'bar',data:{labels:@json($finance['labels']),datasets:[{label:'Income',data:@json($finance['incomeSeries']),backgroundColor:'#10b981'},{label:'Expenditure',data:@json($finance['expenseSeries']),backgroundColor:'#f43f5e'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{callback:value=>new Intl.NumberFormat('en',{notation:'compact'}).format(value)}}}}});
                    }
                    const position = document.getElementById('bursarPositionChart');
                    if (position && !position.dataset.ready) {
                        position.dataset.ready = '1';
                        new Chart(position, {type:'doughnut',data:{labels:['Collected','Outstanding','Expenses'],datasets:[{data:@json([$finance['income'],$finance['outstanding'],$finance['expenses']]),backgroundColor:['#10b981','#facc15','#f43f5e']}]},options:{responsive:true,maintainAspectRatio:false}});
                    }
                };
                document.addEventListener('livewire:navigated', draw);
                setTimeout(draw, 0);
            })();
        </script>
    @elseif($isTeacherWorkspace)
        @if($teacher['nextLesson'])
            @php($nextLesson=$teacher['nextLesson'])
            <section
                x-data="{ now: Date.now(), start: new Date(@js($nextLesson->starts_at_iso)).getTime(), end: new Date(@js($nextLesson->ends_at_iso)).getTime(), timer: null, remaining() { const target=this.now < this.start ? this.start : this.end; const seconds=Math.max(0,Math.floor((target-this.now)/1000)); const h=Math.floor(seconds/3600),m=Math.floor((seconds%3600)/60),s=seconds%60; return (h?h+'h ':'')+m+'m '+String(s).padStart(2,'0')+'s'; }, init(){ this.timer=setInterval(()=>this.now=Date.now(),1000) } }"
                class="relative overflow-hidden rounded-2xl border border-amber-300 bg-gradient-to-r from-amber-50 to-white p-6 shadow-sm"
            >
                <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[10px] font-black uppercase tracking-[.18em] text-amber-700" x-text="now >= start && now < end ? 'Lesson in progress' : 'Your next lesson'"></p>
                        <h2 class="mt-2 text-xl font-black text-slate-900">{{ $nextLesson->subject ?: $nextLesson->label ?: 'Lesson' }}</h2>
                        <p class="mt-1 text-sm font-semibold text-slate-600">{{ $nextLesson->class ?: 'Unassigned class' }}{{ $nextLesson->stream ? ' · '.$nextLesson->stream : '' }} · {{ substr($nextLesson->starts_at,0,5) }}–{{ substr($nextLesson->ends_at,0,5) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-900 px-6 py-4 text-center text-white shadow-lg">
                        <p class="text-[9px] font-bold uppercase tracking-wider text-slate-400" x-text="now >= start && now < end ? 'Time remaining' : 'Starts in'"></p>
                        <p class="mt-1 font-mono text-2xl font-black text-amber-300" x-text="remaining()"></p>
                    </div>
                </div>
                <div class="absolute -bottom-16 -right-10 h-40 w-40 rounded-full bg-amber-300/20 blur-3xl"></div>
            </section>
        @else
            <section class="rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-500">You have no remaining lessons today.</section>
        @endif

        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <p class="text-sm font-bold text-slate-900">@if($teacher['classTeacherClasses']->isNotEmpty())You are the class teacher of {{ $teacher['classTeacherClasses']->pluck('name')->join(', ') }}.@endif @if($teacher['assignments']->isNotEmpty())You are a subject teacher of {{ $teacher['assignments']->pluck('subject')->filter()->unique()->join(', ') }}.@endif</p>
                    @if($teacher['pendingAttendance']->isNotEmpty())
                        <p class="mt-2 text-xs leading-relaxed text-slate-500"><span class="font-bold text-amber-700">Reminder:</span> Attendance is pending for {{ $teacher['pendingAttendance']->pluck('label')->join(', ') }}.</p>
                    @endif
                </div>
                @if($teacher['pendingAttendance']->isNotEmpty())
                    <div class="flex shrink-0 flex-wrap gap-2">
                        @if($teacher['pendingAttendance']->where('type','class')->isNotEmpty())<a wire:navigate href="{{ route('attendance.index') }}" class="rounded-lg bg-yellow-400 px-3 py-2 text-xs font-bold text-slate-950">Take attendance now</a>@endif
                        @if($teacher['pendingAttendance']->where('type','subject')->isNotEmpty())<a wire:navigate href="{{ route('attendance.subject') }}" class="rounded-lg border border-yellow-400 px-3 py-2 text-xs font-bold text-yellow-700">Take subject attendance</a>@endif
                    </div>
                @endif
            </div>
        </section>

        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3">
            @foreach([
                ['Assigned subjects',$teacher['subjects'],'Subjects allocated this term'],
                ['Assigned classes',$teacher['classes'],'Classes in your teaching load'],
                ['Learners',$teacher['learners'],'Active learners in assigned classes'],
                ['Lessons today',$teacher['lessonsToday'],now()->format('l').' timetable'],
                ['Attendance today',$teacher['attendanceToday'],'Learner records captured by you'],
                ['Pending mark sheets',$teacher['pendingPapers'],'Draft or rejected submissions'],
            ] as [$label,$value,$note])
                <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-wide text-slate-400">{{ $label }}</p>
                    <p class="mt-2 text-3xl font-bold text-slate-900">{{ number_format($value) }}</p>
                    <p class="mt-1 text-xs text-slate-500">{{ $note }}</p>
                </section>
            @endforeach
        </div>

        <div class="grid gap-5 xl:grid-cols-2">
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-900">Attendance activity</h2>
                <p class="text-xs text-slate-500">Attendance records captured by you during the last seven days.</p>
                <div class="mt-4 h-72"><canvas id="teacherAttendanceChart"></canvas></div>
            </section>
            <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm">
                <h2 class="font-bold text-slate-900">Assigned-subject performance</h2>
                <p class="text-xs text-slate-500">Current-term averages from marks entered for your assignments.</p>
                <div class="mt-4 h-72"><canvas id="teacherPerformanceChart"></canvas></div>
            </section>
        </div>

        <div class="grid gap-5 xl:grid-cols-3">
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm xl:col-span-2">
                <div class="flex items-center justify-between border-b p-5"><div><h2 class="font-bold">Today’s timetable</h2><p class="text-xs text-slate-500">{{ now()->format('l, d M Y') }}</p></div><a wire:navigate href="{{ route('timetable.index') }}" class="text-xs font-bold text-yellow-700">Full timetable</a></div>
                <div class="divide-y">
                    @forelse($teacher['todayLessons'] as $lesson)
                        <div class="grid gap-2 p-4 text-sm sm:grid-cols-[120px_1fr_auto] sm:items-center">
                            <b class="text-slate-700">{{ substr($lesson->starts_at,0,5) }}–{{ substr($lesson->ends_at,0,5) }}</b>
                            <div><p class="font-semibold">{{ $lesson->subject ?: $lesson->label ?: 'Lesson' }}</p><p class="text-xs text-slate-400">{{ $lesson->class ?: 'Unassigned class' }}{{ $lesson->stream ? ' · '.$lesson->stream : '' }}</p></div>
                            <span class="rounded-full bg-yellow-50 px-3 py-1 text-xs font-bold text-yellow-800">{{ $lesson->label ?: 'Teaching' }}</span>
                        </div>
                    @empty
                        <div class="p-10 text-center text-sm text-slate-400">No lessons assigned for today.</div>
                    @endforelse
                </div>
            </section>
            <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div class="border-b p-5"><h2 class="font-bold">Teaching assignments</h2></div>
                <div class="max-h-80 divide-y overflow-y-auto">
                    @foreach($teacher['classTeacherClasses'] as $classTeacherClass)
                        <div class="bg-amber-50 p-4 text-sm"><p class="font-semibold">{{ $classTeacherClass->name }}</p><p class="text-xs font-bold text-amber-700">Class teacher responsibility</p></div>
                    @endforeach
                    @foreach($teacher['assignments'] as $assignment)
                        <div class="p-4 text-sm"><p class="font-semibold">{{ $assignment->subject }}</p><p class="text-xs text-slate-400">{{ $assignment->class ?: 'All assigned classes' }}{{ $assignment->code ? ' · '.$assignment->code : '' }}</p></div>
                    @endforeach
                    @if($teacher['assignments']->isEmpty() && $teacher['classTeacherClasses']->isEmpty())
                        <div class="p-8 text-center text-sm text-slate-400">No subjects have been assigned.</div>
                    @endif
                </div>
            </section>
        </div>

        <script>
            (() => {
                const drawTeacherCharts = () => {
                    if (typeof Chart === 'undefined') return;
                    const attendance = document.getElementById('teacherAttendanceChart');
                    if (attendance && !attendance.dataset.ready) {
                        attendance.dataset.ready='1';
                        new Chart(attendance,{type:'line',data:{labels:@json($teacher['attendanceLabels']),datasets:[{label:'Present / late',data:@json($teacher['presentSeries']),borderColor:'#10b981',backgroundColor:'rgba(16,185,129,.12)',fill:true,tension:.35},{label:'Absent',data:@json($teacher['absentSeries']),borderColor:'#f43f5e',backgroundColor:'rgba(244,63,94,.1)',fill:true,tension:.35}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,ticks:{precision:0}}}}});
                    }
                    const performance = document.getElementById('teacherPerformanceChart');
                    if (performance && !performance.dataset.ready) {
                        performance.dataset.ready='1';
                        new Chart(performance,{type:'bar',data:{labels:@json($teacher['performanceLabels']),datasets:[{label:'Average %',data:@json($teacher['performanceSeries']),backgroundColor:'#facc15'}]},options:{responsive:true,maintainAspectRatio:false,scales:{y:{beginAtZero:true,max:100}}}});
                    }
                };
                document.addEventListener('livewire:navigated',drawTeacherCharts);
                setTimeout(drawTeacherCharts,0);
            })();
        </script>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($visibleModules as $module=>$definition)
                <a wire:navigate href="{{ route($definition[1]) }}" class="rounded-2xl border bg-white p-5 shadow-sm transition hover:border-yellow-400 hover:bg-yellow-50"><h2 class="font-bold">{{ $definition[0] }}</h2><p class="mt-1 text-sm text-slate-500">Open your permitted {{ strtolower($definition[0]) }} workspace.</p></a>
            @empty
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-amber-900">No modules have been assigned. Contact the school administrator.</div>
            @endforelse
        </div>
        <section class="rounded-2xl border bg-white p-5"><h2 class="font-bold">Upcoming events</h2><div class="mt-3 grid gap-2 md:grid-cols-2">@forelse($events as $event)<div class="rounded-lg bg-slate-50 p-3 text-sm"><b>{{ $event->title }}</b><span class="block text-slate-500">{{ $event->event_date->format('d M Y') }}</span></div>@empty<p class="text-sm text-slate-500">No upcoming events.</p>@endforelse</div></section>
    @endif

    <x-dashboard-quick-actions :actions="$quickActions" :badge="$quickActionBadge" />
</div>
