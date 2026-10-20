<x-app-layout>
@php
    $input = 'w-full rounded-xl border border-slate-200 bg-white px-3 py-2.5 text-sm text-slate-800 focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-200 disabled:border-transparent disabled:bg-slate-50 disabled:text-slate-600';
    $cell = 'px-5 py-3.5';
    $head = 'bg-slate-50 text-left text-[11px] font-bold uppercase tracking-wider text-slate-500';
    $backRoute = \App\Support\TeacherAcademicScope::canViewStudentDirectory(auth()->user()) ? route('students.index') : route('workbench.home');
    $tabs = ['details' => 'Student details', 'academics' => 'Academics & subjects', 'finance' => 'Financial status', 'family' => 'Parents & guardians', 'history' => 'Attendance & history', 'discipline' => 'Discipline & conduct'];
    $photoUrl = $student->photoUrl();
    $latestReport = $examProgress->first();
    $termReport = $studentReports->firstWhere('term_id', $term?->id);
@endphp
<div x-data="{
    tab: 'details', photoPreview: null,
    openTab(section) {
        this.tab = section;
        this.$nextTick(() => document.getElementById('panel-' + section)?.scrollIntoView({ behavior: 'smooth', block: 'start' }));
    }
}" class="w-full max-w-none space-y-6 pb-10">
    <header class="overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-lg sm:p-8">
        <div class="flex flex-col justify-between gap-6 sm:flex-row sm:items-center">
            <div class="flex flex-col items-start gap-5 sm:flex-row sm:items-center">
                <div class="flex h-40 w-40 shrink-0 items-center justify-center overflow-hidden rounded-full border-4 border-amber-300 bg-slate-800 text-4xl font-black text-amber-300 shadow-xl ring-4 ring-white/10 sm:h-48 sm:w-48">
                    @if($photoUrl)
                        <img src="{{ $photoUrl }}" data-original="{{ $photoUrl }}" :src="photoPreview || $el.dataset.original" alt="{{ $student->name }} student photograph" class="h-full w-full object-cover">
                    @else
                        <img x-show="photoPreview" x-cloak :src="photoPreview" alt="New student photograph preview" class="h-full w-full object-cover">
                        <div x-show="!photoPreview" class="px-2 text-center">{{ str($student->name)->substr(0, 1)->upper() }}<span class="mt-2 block text-[10px] font-medium text-slate-300">No photo uploaded</span></div>
                    @endif
                </div>
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.2em] text-amber-300">Student Profile</p>
                    <h1 class="mt-2 text-2xl font-black sm:text-3xl">{{ $student->name }}</h1>
                    <p class="mt-2 text-sm text-slate-300">{{ $student->admission_no ?: 'Admission number pending' }} · {{ $student->schoolClass?->name ?? 'No class assigned' }}{{ $student->stream ? ' · '.$student->stream->name : '' }}</p>
                    <span class="mt-3 inline-flex rounded-full bg-white/10 px-3 py-1 text-xs font-bold text-amber-200">{{ ucfirst($student->status) }}</span>
                </div>
            </div>
            <a href="{{ $backRoute }}" wire:navigate class="self-start rounded-xl border border-white/20 px-4 py-2.5 text-sm font-bold hover:bg-white/10">Back to learners</a>
        </div>
    </header>

    <div aria-label="Student quick actions" class="flex flex-wrap items-center gap-3 rounded-2xl border border-slate-200 bg-white p-4">
        @if($student->isActive() && auth()->user()->hasPermission('students.view'))
            <form method="POST" action="{{ route('students.id-cards.generate') }}" target="_blank" rel="noopener">
                @csrf
                <input type="hidden" name="type" value="student">
                <input type="hidden" name="ids[]" value="{{ $student->id }}">
                <button type="submit" class="rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-bold text-slate-950 hover:bg-amber-300">Print ID card</button>
            </form>
        @endif
        @if($latestReport && auth()->user()->hasPermission('reports.view'))
            <a href="{{ route('reports.student-term-report.show', [$student, $latestReport['exam_id']]) }}" target="_blank" rel="noopener" class="rounded-xl bg-slate-900 px-4 py-2.5 text-sm font-bold text-white hover:bg-slate-700">View report</a>
        @endif
        <button type="button" @click="openTab('academics')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">View academic progress</button>
        @if($student->isActive() && (auth()->user()->hasPermission('finance.payments') || auth()->user()->hasPermission('finance.adjustments')))
            <a href="{{ route('fee-payments.index', ['student' => $student->id]) }}" wire:navigate class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">View balance</a>
        @elseif($canViewFinance)
            <button type="button" @click="openTab('finance')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">Financial summary</button>
        @endif
        @if($student->isActive() && $term?->is_current && auth()->user()->hasPermission('finance.payments'))
            <a href="{{ route('fee-payments.index', ['student' => $student->id]) }}" wire:navigate class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-2.5 text-sm font-bold text-emerald-700 hover:bg-emerald-100">Record payment</a>
        @endif
        <button type="button" @click="openTab('history')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">View attendance</button>
        <button type="button" @click="openTab('family')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">View parents</button>
        <button type="button" @click="openTab('discipline')" class="rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">View conduct</button>
        @if($canEdit)
            <button type="button" @click="openTab('details'); $nextTick(() => document.getElementById('student-photo').click())" class="ml-auto rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:bg-slate-50">{{ $photoUrl ? 'Change photo' : 'Add photo' }}</button>
        @endif
    </div>

    @if(session('status'))<div role="status" class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div role="alert" class="rounded-2xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif

    <div class="flex flex-col justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-5 sm:flex-row sm:items-center">
        <div><h2 class="font-bold text-slate-900">One learner. The full picture.</h2><p class="mt-1 text-xs text-slate-500">Choose a term to review their placement, fees, results and attendance.</p></div>
        <form method="GET" action="{{ route('students.profile', $student) }}" class="min-w-56">
            <label for="profile-term" class="text-xs font-bold text-slate-500">Viewing term</label>
            <select id="profile-term" name="term" onchange="this.form.requestSubmit()" class="{{ $input }} mt-1">
                @forelse($terms as $item)<option value="{{ $item->id }}" @selected($term?->id === $item->id)>{{ $item->name }} · {{ $item->year }}{{ $item->is_current ? ' (current)' : '' }}</option>@empty<option>No terms available</option>@endforelse
            </select>
            <noscript><button class="mt-2 rounded-xl bg-amber-400 px-4 py-2 text-sm font-bold">View term</button></noscript>
        </form>
    </div>

    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Class & stream</p><p class="mt-2 text-lg font-black text-slate-900">{{ $placementClass?->name ?? 'Not assigned' }}</p><p class="mt-1 text-xs text-slate-500">{{ $placementStream?->name ?? 'No stream' }} · {{ $placementCategory?->name ?? 'No category' }}</p></div>
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Daily attendance</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $attendanceRate !== null ? $attendanceRate.'%' : '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ $attendanceTotal }} recorded days · selected term</p></div>
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Academic progress</p><p class="mt-2 text-2xl font-black text-slate-900">{{ $examProgress->count() }}</p><p class="mt-1 text-xs text-slate-500">Exams with approved marks · {{ $subjects->count() }} subjects</p></div>
        <div class="rounded-2xl border bg-white p-5"><p class="text-xs font-bold uppercase tracking-wide text-slate-400">Fee balance</p><p class="mt-2 text-xl font-black {{ $finance && $finance['balance'] > 0 ? 'text-rose-700' : 'text-slate-900' }}">{{ $finance ? $currency.' '.number_format(max(0, $finance['balance'])) : '—' }}</p><p class="mt-1 text-xs text-slate-500">{{ ! $canViewFinance ? 'Finance access required' : (! $finance ? 'No term available' : ($finance['mapped'] === null && $finance['due'] == 0 ? 'No fee assessment recorded' : ($finance['balance'] > 0 ? 'Outstanding amount' : ($finance['balance'] < 0 ? 'Account in credit' : 'Cleared')))) }}</p></div>
    </div>

    <nav role="tablist" aria-label="Student record sections" class="flex gap-2 overflow-x-auto rounded-2xl border border-slate-200 bg-white p-2">
        @foreach($tabs as $key => $label)
            <button type="button" role="tab" id="tab-{{ $key }}" aria-controls="panel-{{ $key }}" :aria-selected="tab === '{{ $key }}'" @click="tab = '{{ $key }}'" :class="tab === '{{ $key }}' ? 'bg-slate-900 text-white shadow-sm' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900'" class="whitespace-nowrap rounded-xl px-4 py-3 text-sm font-bold">{{ $label }}</button>
        @endforeach
    </nav>

    <section id="panel-discipline" role="tabpanel" aria-labelledby="tab-discipline" x-show="tab === 'discipline'" x-cloak>
        <livewire:student-conduct-log :student="$student" :key="'conduct-log-'.$student->id" />
    </section>

    <section id="panel-details" role="tabpanel" aria-labelledby="tab-details" x-show="tab === 'details'" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
        <div class="border-b px-5 py-5 sm:px-6"><h2 class="text-lg font-black text-slate-900">Identity & personal information</h2><p class="mt-1 text-xs text-slate-500">{{ $canEdit ? 'Details are prefilled. Update the editable fields and save below.' : 'You are viewing this learner’s record. Personal details are read-only for your account.' }}</p></div>
        <form method="POST" action="{{ route('students.profile.update', $student) }}" enctype="multipart/form-data">
            @csrf @method('PATCH')
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Field</th><th class="{{ $cell }}">Student information</th><th class="{{ $cell }}">Access</th></tr></thead><tbody class="divide-y divide-slate-100">
                @foreach([
                    ['name', 'Full name', 'text', $student->name], ['admission_no', 'Admission number', 'text', $student->admission_no],
                    ['lin', 'Learner Identification Number (LIN)', 'text', $student->lin], ['date_of_birth', 'Date of birth', 'date', $student->date_of_birth?->toDateString()],
                    ['admission_date', 'Admission date', 'date', $student->admission_date?->toDateString()],
                    ['nationality', 'Nationality', 'text', $student->nationality], ['religion', 'Religion', 'text', $student->religion],
                ] as [$name, $label, $type, $value])
                    <tr><th scope="row" class="{{ $cell }} w-1/3 text-left font-semibold text-slate-600"><label for="student-{{ $name }}">{{ $label }}</label></th><td class="{{ $cell }}"><input id="student-{{ $name }}" type="{{ $type }}" name="{{ $name }}" value="{{ old($name, $value) }}" @required($name === 'name') @disabled(!$canEdit) class="{{ $input }}"></td><td class="{{ $cell }} whitespace-nowrap text-xs text-slate-400">{{ $canEdit ? 'Editable' : 'Read only' }}</td></tr>
                @endforeach
                <tr><th scope="row" class="{{ $cell }} text-left font-semibold text-slate-600"><label for="student-gender">Gender</label></th><td class="{{ $cell }}"><select id="student-gender" name="gender" @disabled(!$canEdit) class="{{ $input }}"><option value="">Not specified</option>@foreach(['male' => 'Male', 'female' => 'Female'] as $value => $label)<option value="{{ $value }}" @selected(old('gender', $student->gender) === $value)>{{ $label }}</option>@endforeach</select></td><td class="{{ $cell }} text-xs text-slate-400">{{ $canEdit ? 'Editable' : 'Read only' }}</td></tr>
                <tr><th scope="row" class="{{ $cell }} text-left font-semibold text-slate-600"><label for="student-address">Home address</label></th><td class="{{ $cell }}"><textarea id="student-address" name="home_address" @disabled(!$canEdit) class="{{ $input }}" rows="2">{{ old('home_address', $student->home_address) }}</textarea></td><td class="{{ $cell }} text-xs text-slate-400">{{ $canEdit ? 'Editable' : 'Read only' }}</td></tr>
                @if($canEdit)
                    <tr><th scope="row" class="{{ $cell }} text-left font-semibold text-slate-600"><label for="student-blood">Blood group</label></th><td class="{{ $cell }}"><input id="student-blood" name="blood_group" value="{{ old('blood_group', $student->blood_group) }}" class="{{ $input }}"></td><td class="{{ $cell }} text-xs text-slate-400">Editable</td></tr>
                    <tr><th scope="row" class="{{ $cell }} text-left font-semibold text-slate-600"><label for="student-medical">Medical notes</label></th><td class="{{ $cell }}"><textarea id="student-medical" name="medical_notes" class="{{ $input }}" rows="3">{{ old('medical_notes', $student->medical_notes) }}</textarea></td><td class="{{ $cell }} text-xs text-slate-400">Editable</td></tr>
                @endif
                @foreach(['School' => $student->school->name, 'Current class' => $student->schoolClass?->name, 'Current stream' => $student->stream?->name, 'Enrolment category' => $student->category?->name, 'Status' => ucfirst($student->status), 'House' => $houses->join(', '), 'Clubs' => $clubs->join(', ')] as $label => $value)
                    <tr class="bg-slate-50/50"><th scope="row" class="{{ $cell }} text-left font-semibold text-slate-600">{{ $label }}</th><td class="{{ $cell }} text-slate-800">{{ $value ?: 'Not recorded' }}</td><td class="{{ $cell }} whitespace-nowrap text-xs text-slate-400">Read only</td></tr>
                @endforeach
            </tbody></table></div>
            @if($canEdit)<div class="flex flex-col gap-4 border-t bg-slate-50 p-5 sm:flex-row sm:items-end sm:justify-between"><label class="text-xs font-bold text-slate-600">Update profile photograph<input id="student-photo" type="file" name="photo" accept="image/jpeg,image/png,image/webp" @change="if (photoPreview) URL.revokeObjectURL(photoPreview); photoPreview = $event.target.files[0] ? URL.createObjectURL($event.target.files[0]) : null" class="mt-2 block max-w-full text-xs"><span class="mt-1 block font-normal text-slate-400">JPG, PNG or WebP · maximum 4 MB. Choose a photo, then save to apply it.</span></label><button class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-black text-slate-950 hover:bg-amber-300">Save student details</button></div>@endif
        </form>
    </section>

    <section id="panel-academics" role="tabpanel" aria-labelledby="tab-academics" x-show="tab === 'academics'" x-cloak class="space-y-5">
        <div x-data="{ reportScope: 'term', reportId: {{ $termReport['id'] ?? 0 }} }" class="overflow-hidden rounded-2xl border border-slate-200 bg-white">
            <div class="flex flex-col justify-between gap-4 border-b p-5 sm:flex-row sm:items-center">
                <div><h2 class="text-lg font-black">Student reports</h2><p class="mt-1 text-xs text-slate-500">Select a report to see {{ $student->name }}’s approved marks or open the full report.</p></div>
                <div class="flex gap-1 rounded-xl bg-slate-100 p-1" aria-label="Report history filter">
                    <button type="button" @click="reportScope = 'term'; reportId = {{ $termReport['id'] ?? 0 }}" :aria-pressed="reportScope === 'term'" :class="reportScope === 'term' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-bold">Selected term</button>
                    <button type="button" @click="reportScope = 'all'; reportId = {{ $studentReports->first()['id'] ?? 0 }}" :aria-pressed="reportScope === 'all'" :class="reportScope === 'all' ? 'bg-white text-slate-900 shadow-sm' : 'text-slate-500'" class="whitespace-nowrap rounded-lg px-3 py-2 text-xs font-bold">All terms</button>
                </div>
            </div>
            @if($studentReports->isNotEmpty())
                <div role="tablist" aria-label="Available student reports" class="flex gap-2 overflow-x-auto border-b bg-slate-50 p-3">
                    @foreach($studentReports as $report)
                        <button type="button" role="tab" id="report-tab-{{ $report['id'] }}" aria-controls="report-panel-{{ $report['id'] }}" x-show="reportScope === 'all' || {{ $report['term_id'] }} === {{ $term?->id ?? 0 }}" @click="reportId = {{ $report['id'] }}" :aria-selected="reportId === {{ $report['id'] }}" :class="reportId === {{ $report['id'] }} ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-amber-300'" class="shrink-0 rounded-xl border px-4 py-3 text-left text-sm font-bold">
                            {{ $report['name'] }}<span class="mt-1 block text-[11px] font-normal opacity-70">{{ $report['term_name'] }} · {{ $report['year'] }}</span>
                        </button>
                    @endforeach
                </div>
                @foreach($studentReports as $report)
                    <div role="tabpanel" id="report-panel-{{ $report['id'] }}" aria-labelledby="report-tab-{{ $report['id'] }}" data-student-report="{{ $report['id'] }}" x-show="reportId === {{ $report['id'] }} && (reportScope === 'all' || {{ $report['term_id'] }} === {{ $term?->id ?? 0 }})" x-cloak>
                        <div class="flex flex-wrap items-center justify-between gap-4 p-5">
                            <div><h3 class="font-black text-slate-900">{{ $report['name'] }}</h3><p class="mt-1 text-xs text-slate-500">{{ $report['term_name'] }} · {{ $report['year'] }} · {{ $report['published'] ? 'Published' : 'Approved marks · not published' }}</p></div>
                            @if($report['can_open'])<a href="{{ route('reports.student-term-report.show', [$student, $report['id']]) }}" target="_blank" rel="noopener" class="rounded-xl bg-amber-400 px-4 py-2.5 text-xs font-bold text-slate-950 hover:bg-amber-300">Open full report</a>@endif
                        </div>
                        <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Subject</th><th class="{{ $cell }}">Marks scored</th><th class="{{ $cell }}">Out of</th><th class="{{ $cell }}">Percentage</th></tr></thead><tbody class="divide-y">
                            @foreach($report['marks'] as $mark)<tr><td class="{{ $cell }} font-semibold">{{ $mark->subject_name }}</td><td class="{{ $cell }} font-mono">{{ number_format($mark->score, 1) }}</td><td class="{{ $cell }} font-mono">{{ number_format($mark->maximum_score, 1) }}</td><td class="{{ $cell }} font-mono">{{ $mark->percentage !== null ? number_format($mark->percentage, 1).'%' : '—' }}</td></tr>@endforeach
                        </tbody></table></div>
                        <div class="flex flex-wrap justify-between gap-2 border-t bg-slate-50 px-5 py-4 text-xs text-slate-500"><span>{{ $report['marks']->count() }} approved papers</span><span>Recorded average: <b class="text-slate-800">{{ $report['average'] !== null ? number_format($report['average'], 1).'%' : '—' }}</b></span></div>
                    </div>
                @endforeach
                <p x-show="reportScope === 'term' && reportId === 0" class="p-5 text-sm text-slate-500">No approved reports for the selected term. Choose All terms to view earlier reports.</p>
            @else
                <p class="p-5 text-sm text-slate-500">No approved reports or marks have been recorded for this student.</p>
            @endif
        </div>
        <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="text-lg font-black">Subjects & progress</h2><p class="mt-1 text-xs text-slate-500">Subjects offered for this class and term, including individual selections where applicable.</p></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Subject</th><th class="{{ $cell }}">Selection</th><th class="{{ $cell }}">Approved papers</th><th class="{{ $cell }}">Recorded average</th></tr></thead><tbody class="divide-y">@forelse($subjects as $subject)<tr><td class="{{ $cell }}"><b>{{ $subject->name }}</b><span class="ml-2 text-xs text-slate-400">{{ $subject->code }}</span></td><td class="{{ $cell }}">{{ $subject->selection_type ? ucfirst($subject->selection_type) : 'Class subject' }}</td><td class="{{ $cell }}">{{ $subject->assessments }}</td><td class="{{ $cell }} font-mono">{{ $subject->average !== null ? number_format($subject->average, 1).'%' : 'Not assessed' }}</td></tr>@empty<tr><td colspan="4" class="{{ $cell }} text-slate-400">No subjects have been assigned for this term.</td></tr>@endforelse</tbody></table></div></div>
    </section>

    <section id="panel-finance" role="tabpanel" aria-labelledby="tab-finance" x-show="tab === 'finance'" x-cloak class="space-y-5">
        @if(!$canViewFinance)<div class="rounded-2xl border bg-white p-6 text-sm text-slate-500">Financial records are available to staff with finance permissions.</div>
        @elseif(!$finance)<div class="rounded-2xl border bg-white p-6 text-sm text-slate-500">Open or select a term to review this learner’s financial status.</div>
        @else
            <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="text-lg font-black">Financial status</h2><p class="mt-1 text-xs text-slate-500">Calculated from the fee record, approved adjustments and posted payments. Amounts are read-only.</p></div><table class="w-full text-sm"><tbody class="divide-y">@foreach(['Mapped fee' => $finance['mapped'], 'Approved fee reductions' => $finance['adjustments'], 'Arrears brought forward' => $finance['arrears'], 'Total due' => $finance['due'], 'Posted payments' => $finance['paid'], 'Outstanding balance' => max(0, $finance['balance']), 'Credit balance' => max(0, -$finance['balance'])] as $label => $amount)<tr><th scope="row" class="{{ $cell }} text-left font-semibold text-slate-600">{{ $label }}</th><td class="{{ $cell }} text-right font-mono font-bold">{{ $amount === null ? 'Not assessed' : $currency.' '.number_format($amount, 2) }}</td></tr>@endforeach</tbody></table></div>
            <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="text-lg font-black">Recent payments</h2><p class="mt-1 text-xs text-slate-500">Latest 20 approved payments in the selected term.</p></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Date</th><th class="{{ $cell }}">Method / reference</th><th class="{{ $cell }}">Received by</th><th class="{{ $cell }}">Amount</th></tr></thead><tbody class="divide-y">@forelse($payments as $payment)<tr><td class="{{ $cell }} whitespace-nowrap">{{ $payment->paid_at?->format('d M Y') }}</td><td class="{{ $cell }}">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}<span class="mt-1 block text-xs text-slate-400">{{ $payment->transaction_id ?: $payment->bank_slip_number ?: 'No reference' }}</span></td><td class="{{ $cell }}">{{ $payment->recordedBy?->name ?? 'Not recorded' }}</td><td class="{{ $cell }} whitespace-nowrap font-mono font-bold text-emerald-700">{{ $currency }} {{ number_format($payment->amount, 2) }}</td></tr>@empty<tr><td colspan="4" class="{{ $cell }} text-slate-400">No posted payments for this term.</td></tr>@endforelse</tbody></table></div></div>
        @endif
    </section>

    <section id="panel-family" role="tabpanel" aria-labelledby="tab-family" x-show="tab === 'family'" x-cloak class="space-y-5">
        <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="text-lg font-black">Parents & guardians</h2><p class="mt-1 text-xs text-slate-500">Contact details attached to this learner’s record.</p></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Name</th><th class="{{ $cell }}">Relationship</th><th class="{{ $cell }}">Phone</th><th class="{{ $cell }}">Email</th><th class="{{ $cell }}">Primary</th></tr></thead><tbody class="divide-y">@forelse($student->guardians as $guardian)<tr><td class="{{ $cell }} font-bold">{{ $guardian->name }}</td><td class="{{ $cell }}">{{ $guardian->relationship }}</td><td class="{{ $cell }}">{{ $guardian->phone ?: 'Not recorded' }}</td><td class="{{ $cell }} break-all">{{ $guardian->email ?: 'Not recorded' }}</td><td class="{{ $cell }}">{{ $guardian->is_primary ? 'Yes' : 'No' }}</td></tr>@empty<tr><td colspan="5" class="{{ $cell }} text-slate-400">No guardian contact details recorded.</td></tr>@endforelse</tbody></table></div></div>
        <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="font-black">Linked parent accounts</h2></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Parent</th><th class="{{ $cell }}">Login email</th><th class="{{ $cell }}">Phone</th><th class="{{ $cell }}">Account</th></tr></thead><tbody class="divide-y">@forelse($parentAccounts as $parent)<tr><td class="{{ $cell }} font-bold">{{ $parent->name }}</td><td class="{{ $cell }} break-all">{{ $parent->email }}</td><td class="{{ $cell }}">{{ $parent->phone ?: 'Not recorded' }}</td><td class="{{ $cell }}">@if(auth()->user()->hasPermission('parents.manage'))<a href="{{ route('parents.profile', $parent) }}" wire:navigate class="text-xs font-bold text-amber-700">Edit parent</a>@else<span class="text-xs text-slate-400">Read only</span>@endif</td></tr>@empty<tr><td colspan="4" class="{{ $cell }} text-slate-400">No parent login linked.</td></tr>@endforelse</tbody></table></div></div>
    </section>

    <section id="panel-history" role="tabpanel" aria-labelledby="tab-history" x-show="tab === 'history'" x-cloak class="space-y-5">
        <div class="grid gap-3 sm:grid-cols-4">@foreach(['present' => 'Present', 'absent' => 'Absent', 'late' => 'Late', 'excused' => 'Excused'] as $status => $label)<div class="rounded-2xl border bg-white p-5"><p class="text-xs font-bold uppercase text-slate-400">{{ $label }}</p><p class="mt-2 text-2xl font-black">{{ $attendanceCounts[$status] ?? 0 }}</p><p class="text-xs text-slate-400">Daily register · selected term</p></div>@endforeach</div>
        <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="text-lg font-black">Attendance register</h2><p class="mt-1 text-xs text-slate-500">Latest 30 daily and subject records for this term.</p></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Date</th><th class="{{ $cell }}">Register</th><th class="{{ $cell }}">Status</th></tr></thead><tbody class="divide-y">@forelse($attendance as $record)<tr><td class="{{ $cell }}">{{ $record->attendance_date->format('d M Y') }}</td><td class="{{ $cell }}">{{ $record->subject?->name ?? 'Daily attendance' }}</td><td class="{{ $cell }}"><span class="rounded-full {{ $record->status === 'absent' ? 'bg-rose-50 text-rose-700' : 'bg-slate-100 text-slate-700' }} px-3 py-1 text-xs font-bold">{{ ucfirst($record->status) }}</span></td></tr>@empty<tr><td colspan="3" class="{{ $cell }} text-slate-400">No attendance records for this term.</td></tr>@endforelse</tbody></table></div></div>
        <div class="overflow-hidden rounded-2xl border bg-white"><div class="border-b p-5"><h2 class="text-lg font-black">Enrolment history</h2></div><div class="overflow-x-auto"><table class="w-full text-sm"><thead class="{{ $head }}"><tr><th class="{{ $cell }}">Term</th><th class="{{ $cell }}">Class / stream</th><th class="{{ $cell }}">Category</th><th class="{{ $cell }}">Outcome</th><th class="{{ $cell }}">Status</th></tr></thead><tbody class="divide-y">@forelse($enrolments as $enrolment)<tr><td class="{{ $cell }}">{{ $enrolment->term?->name }} · {{ $enrolment->term?->year }}</td><td class="{{ $cell }}">{{ $enrolment->schoolClass?->name ?? 'Not assigned' }}{{ $enrolment->stream ? ' · '.$enrolment->stream->name : '' }}</td><td class="{{ $cell }}">{{ $enrolment->category?->name ?? 'Not assigned' }}</td><td class="{{ $cell }}">{{ $enrolment->promotion_outcome ? ucfirst(str_replace('_', ' ', $enrolment->promotion_outcome)) : 'Not recorded' }}</td><td class="{{ $cell }}">{{ ucfirst($enrolment->status) }}</td></tr>@empty<tr><td colspan="5" class="{{ $cell }} text-slate-400">No historical enrolments recorded.</td></tr>@endforelse</tbody></table></div></div>
    </section>
</div>
</x-app-layout>
