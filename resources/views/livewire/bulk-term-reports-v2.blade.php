<div class="mx-auto max-w-6xl">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: white !important; }
            .bulk-report { width: 190mm; min-height: 277mm; margin: 0 auto; break-after: page; page-break-after: always; box-shadow: none !important; }
            .bulk-report:last-child { break-after: auto; page-break-after: auto; }
        }
    </style>

    <div class="mb-8 print:hidden">
        <h1 class="text-2xl font-bold">Bulk term reports</h1>
        <p class="text-slate-500">Uses each class's saved education stage, grading scale, and report settings.</p>
    </div>

    <div class="mb-6 grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2 print:hidden">
        <label class="text-sm font-semibold">Term
            <select wire:model.live="termId" class="mt-1 w-full rounded-xl border-slate-200">
                @foreach($terms as $item)<option value="{{$item->id}}">{{$item->name}}, {{$item->year}}</option>@endforeach
            </select>
        </label>
        <label class="text-sm font-semibold">Class
            <select wire:model.live="classId" class="mt-1 w-full rounded-xl border-slate-200">
                @foreach($classes as $item)<option value="{{$item->id}}">{{$item->name}} · {{str($item->education_stage)->replace('_',' ')->title()}}</option>@endforeach
            </select>
        </label>
    </div>

    <div class="mb-6 flex items-center justify-between print:hidden">
        <p class="text-sm text-slate-500">{{ $reports->count() }} report{{ $reports->count() === 1 ? '' : 's' }} ready</p>
        <button onclick="window.print()" @disabled($reports->isEmpty()) class="rounded-xl bg-slate-900 px-5 py-3 font-bold text-white disabled:opacity-40">Print class reports</button>
    </div>

    @forelse($reports as $report)
        @php($student=$report['student']) @php($data=$report['data']) @php($settings=$data['settings'])
        <article class="bulk-report relative isolate mb-8 overflow-hidden rounded-2xl border bg-white p-8 print:mb-0 print:rounded-none print:border-0">
            <div class="pointer-events-none absolute inset-0 z-0 flex items-center justify-center" aria-hidden="true">
                @if($school->badgeUrl())
                    <img src="{{ $school->badgeUrl() }}" class="h-72 w-72 object-contain opacity-[0.06] grayscale" alt="">
                @else
                    <span class="-rotate-45 text-6xl font-black uppercase tracking-widest text-slate-900 opacity-[0.04]">{{$school->name}}</span>
                @endif
            </div>
            <div class="relative z-10">
            <header class="flex items-center gap-5 border-b-2 border-slate-900 pb-4">
                @if($school->badgeUrl())<img src="{{ $school->badgeUrl() }}" class="h-20 w-20 object-contain" alt="School badge">@endif
                <div class="flex-1 text-center">
                    <h2 class="text-2xl font-black uppercase">{{$school->name}}</h2>
                    @if($school->motto)<p class="text-sm italic">{{$school->motto}}</p>@endif
                    <h3 class="mt-2 font-bold uppercase">Term report · {{$term->name}}, {{$term->year}}</h3>
                    <p class="text-xs text-slate-500">{{str($data['stage'])->replace('_',' ')->title()}} grading profile</p>
                </div>
            </header>

            <div class="mt-4 grid grid-cols-2 gap-x-8 gap-y-2 border-y py-3 text-sm">
                <p><b>Learner:</b> {{$student->name}}</p><p><b>Admission no:</b> {{$student->admission_no ?: '—'}}</p>
                <p><b>Class:</b> {{$data['class']?->name ?: '—'}}</p><p><b>Issue date:</b> {{now()->format('d M Y')}}</p>
            </div>

            @unless($data['scale_configured'])
                <div class="mt-4 rounded-lg border border-rose-300 bg-rose-50 p-3 text-sm font-semibold text-rose-800">No saved grading scale exists for this education stage. Configure it before issuing this report.</div>
            @endunless

            <table class="mt-5 w-full border-collapse text-sm">
                <thead><tr class="bg-slate-900 text-white"><th class="border p-2 text-left">Subject</th>@if($settings['show_marks'])<th class="border p-2">Marks scored</th>@endif @if($settings['show_maximum'])<th class="border p-2">Maximum marks</th>@endif @if($settings['show_percentage'])<th class="border p-2">%</th>@endif @if($settings['show_grade'])<th class="border p-2">Grade</th>@endif @if($settings['show_points'])<th class="border p-2">Points</th>@endif @if($settings['show_remarks'])<th class="border p-2 text-left">Remark</th>@endif</tr></thead>
                <tbody>
                @forelse($data['marks'] as $mark)
                    <tr><td class="border p-2">{{$mark['subject']}}</td>@if($settings['show_marks'])<td class="border p-2 text-center">{{number_format($mark['score'],1)}}</td>@endif @if($settings['show_maximum'])<td class="border p-2 text-center">{{number_format($mark['maximum'],1)}}</td>@endif @if($settings['show_percentage'])<td class="border p-2 text-center">{{number_format($mark['percentage'],1)}}%</td>@endif @if($settings['show_grade'])<td class="border p-2 text-center font-bold">{{$mark['grade']}}</td>@endif @if($settings['show_points'])<td class="border p-2 text-center">{{$mark['points'] ?? '—'}}</td>@endif @if($settings['show_remarks'])<td class="border p-2">{{$mark['comment']}}</td>@endif</tr>
                @empty
                    <tr><td colspan="{{1 + collect(['show_marks','show_maximum','show_percentage','show_grade','show_points','show_remarks'])->filter(fn($key) => $settings[$key])->count()}}" class="border p-6 text-center text-slate-500">No approved marks are available for this term.</td></tr>
                @endforelse
                </tbody>
            </table>

            <table class="mt-4 w-full border-collapse text-center text-sm">
                <thead><tr class="bg-slate-900 text-xs uppercase text-white">@if($settings['show_points'])<th class="border p-2">Aggregate</th>@endif<th class="border p-2">Average</th><th class="border p-2">Result</th>@if($settings['show_position'])<th class="border p-2">Position</th>@endif</tr></thead>
                <tbody><tr class="font-black">@if($settings['show_points'])<td class="border p-2 text-lg">{{$data['aggregate']}}</td>@endif<td class="border p-2 text-lg">{{number_format($data['average'],1)}}%</td><td class="border p-2">{{$data['marks']->isEmpty()?'Pending':($data['passed']?'Pass':'Below pass mark')}}</td>@if($settings['show_position'])<td class="border p-2 text-lg">{{$positions[$student->id]??'—'}}</td>@endif</tr></tbody>
            </table>
            <p class="mt-1 text-center text-[10px] text-slate-500">Average and aggregate use the best {{$settings['best']}} subject{{$settings['best']===1?'':'s'}} · Pass mark {{number_format($settings['pass'],0)}}%</p>

            @if($settings['show_attendance'])
                <p class="mt-4 border p-3 text-sm"><b>Attendance:</b> {{$data['attendance_present']}} of {{$data['attendance_total']}} recorded days present/late</p>
            @endif
            @if($settings['show_fees'])
                <div class="mt-3 grid grid-cols-3 border text-center text-sm"><p class="p-2"><b>Fees due</b><br>UGX {{number_format($data['fees']['due'])}}</p><p class="border-x p-2"><b>Paid</b><br>UGX {{number_format($data['fees']['paid'])}}</p><p class="p-2"><b>Balance</b><br>UGX {{number_format($data['fees']['balance'])}}</p></div>
            @endif
            @if($settings['show_promotion'])<p class="mt-3 border p-3 text-sm"><b>Promotion decision:</b> {{str($data['promotion']?:'pending')->title()}}</p>@endif
            @if($settings['next_term_starts'])<p class="mt-3 border p-3 text-center text-sm"><b>Next term starts:</b> {{\Carbon\Carbon::parse($settings['next_term_starts'])->format('l, d F Y')}}</p>@endif
            @if($settings['footer'])<p class="mt-4 text-center text-sm italic">{{$settings['footer']}}</p>@endif

            <footer class="mt-10 grid grid-cols-2 gap-16 text-sm"><p class="border-t pt-2 text-center">Class teacher</p><p class="border-t pt-2 text-center">Head teacher</p></footer>
            </div>
        </article>
    @empty
        <div class="rounded-2xl border bg-white p-10 text-center text-slate-500">Select a term and class containing active learners.</div>
    @endforelse
</div>
