<div class="mx-auto max-w-6xl">
    <style>
        @media print {
            @page { size: A4 portrait; margin: 10mm; }
            body { background: white !important; }
            body > *:not(#app-main) { display: none !important; }
            #app-sidebar, #app-main > header { display: none !important; }
            #app-main { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            #app-main > main { display: block !important; width: 100% !important; margin: 0 !important; padding: 0 !important; }
            #app-main > main > div { width: 100% !important; max-width: none !important; margin: 0 !important; padding: 0 !important; }
            .report-sheet { display: block; width: 190mm; min-height: 277mm; margin: 0 auto; break-inside: avoid-page; page-break-inside: avoid; break-after: page; page-break-after: always; box-shadow: none !important; }
            .report-sheet + .report-sheet { break-before: page; page-break-before: always; }
            .report-sheet:last-of-type { break-after: auto; page-break-after: auto; }
        }
    </style>

    <div class="mb-8 print:hidden"><h1 class="text-2xl font-bold">Bulk term reports</h1><p class="text-slate-500">The same report-card layout and saved settings used by individual learner reports.</p></div>
    <div class="mb-6 grid gap-4 rounded-2xl border bg-white p-5 md:grid-cols-2 print:hidden">
        <label class="text-sm font-semibold">Term<select wire:model.live="termId" class="mt-1 w-full rounded-xl border-slate-200">@foreach($terms as $item)<option value="{{$item->id}}">{{$item->name}}, {{$item->year}}</option>@endforeach</select></label>
        <label class="text-sm font-semibold">Class<select wire:model.live="classId" class="mt-1 w-full rounded-xl border-slate-200">@foreach($classes as $item)<option value="{{$item->id}}">{{$item->name}} · {{str($item->education_stage)->replace('_',' ')->title()}}</option>@endforeach</select></label>
    </div>
    <div class="mb-6 flex items-center justify-between print:hidden"><p class="text-sm text-slate-500">{{$reports->count()}} report{{ $reports->count()===1?'':'s' }} ready</p><button onclick="window.print()" @disabled($reports->isEmpty()) class="rounded-xl bg-yellow-400 px-5 py-3 font-bold text-slate-950 disabled:opacity-40">Print class reports</button></div>

    @forelse($reports as $report)
        @php($student=$report['student']) @php($data=$report['data']) @php($settings=$data['settings'])
        <article class="report-sheet mb-8 flex flex-col rounded-2xl border-2 border-slate-900 bg-white p-8 text-slate-900 shadow-sm print:mb-0 print:rounded-none">
            <header class="flex items-start gap-5 border-b-2 border-slate-900 pb-4">
                <div class="flex h-24 w-24 shrink-0 items-center justify-center border-2 border-slate-900 bg-white">
                    @if($school->badgeUrl())<img src="{{$school->badgeUrl()}}" class="h-full w-full object-contain" alt="School badge">@else<span class="text-xs font-bold text-slate-400">SCHOOL BADGE</span>@endif
                </div>
                <div class="flex-1 text-center">
                    <h2 class="text-2xl font-black uppercase tracking-tight">{{$school->name}}</h2>
                    @if($school->motto)<p class="mt-1 text-xs font-bold italic text-amber-700">“{{$school->motto}}”</p>@endif
                    @if($school->address)<p class="mt-1 text-[11px] font-semibold uppercase text-slate-600">{{$school->address}}</p>@endif
                    <p class="mt-1 text-[10px] font-semibold text-slate-600">{{collect([$school->phone,$school->email,$school->website])->filter()->join(' · ')}}</p>
                    <p class="mt-2 text-xs font-black uppercase tracking-wider">{{$term->name}}, {{$term->year}} · {{str($data['stage'])->replace('_',' ')->title()}}</p>
                    <div class="mt-2 inline-block bg-yellow-400 px-8 py-1 text-sm font-black uppercase tracking-widest">Term report</div>
                </div>
                <div class="flex h-24 w-20 shrink-0 items-center justify-center border-2 border-slate-900 bg-slate-50"><span class="px-1 text-center text-[10px] font-bold uppercase text-slate-400">Affix photo</span></div>
            </header>

            <section class="my-5 grid grid-cols-2 gap-x-8 gap-y-2 text-xs font-semibold">
                <p class="border-b border-slate-900 pb-1"><b class="uppercase">Student:</b> {{$student->name}}</p><p class="border-b border-slate-900 pb-1"><b class="uppercase">Admission no:</b> {{$student->admission_no?:'—'}}</p>
                <p class="border-b border-slate-900 pb-1"><b class="uppercase">Class:</b> {{$data['class']?->name?:'—'}}</p><p class="border-b border-slate-900 pb-1"><b class="uppercase">Issue date:</b> {{now()->format('d/m/Y')}}</p>
            </section>

            @unless($data['scale_configured'])<div class="mb-4 border-2 border-rose-600 bg-rose-50 p-3 text-xs font-bold text-rose-700">No saved grading scale exists for this education stage.</div>@endunless

            <table class="w-full border-collapse text-xs">
                <thead><tr class="bg-slate-900 text-white"><th class="border-2 border-slate-900 p-2">No.</th><th class="border-2 border-slate-900 p-2 text-left">Subject</th>
                    @if($settings['show_exam_breakdown'])@foreach($data['examColumns'] as $column)<th class="border-2 border-slate-900 p-1">{{$column['name']}}<small class="block normal-case">Score / %</small></th>@endforeach @endif
                    @if($settings['show_marks'])<th class="border-2 border-slate-900 p-2">Final score</th>@endif @if($settings['show_maximum'])<th class="border-2 border-slate-900 p-2">Maximum</th>@endif @if($settings['show_percentage'])<th class="border-2 border-slate-900 p-2">Final %</th>@endif @if($settings['show_grade'])<th class="border-2 border-slate-900 p-2">Grade</th>@endif @if($settings['show_points'])<th class="border-2 border-slate-900 p-2">Points</th>@endif @if($settings['show_remarks'])<th class="border-2 border-slate-900 p-2 text-left">Remarks</th>@endif
                </tr></thead>
                <tbody>
                @forelse($data['marks'] as $index=>$mark)@php($attempts=collect($mark['assessments'])->keyBy('exam_id'))<tr><td class="border-2 border-slate-900 p-2 text-center">{{$index+1}}</td><td class="border-2 border-slate-900 p-2 font-bold">{{$mark['subject']}}</td>
                    @if($settings['show_exam_breakdown'])@foreach($data['examColumns'] as $column)@php($attempt=$attempts->get($column['exam_id']))<td class="border-2 border-slate-900 p-1 text-center">@if($attempt)<span class="block">{{number_format($attempt['score'],1)}}/{{number_format($attempt['maximum'],1)}}</span><small>{{number_format($attempt['percentage'],1)}}%</small>@else—@endif</td>@endforeach @endif
                    @if($settings['show_marks'])<td class="border-2 border-slate-900 p-2 text-center">{{number_format($mark['score'],1)}}</td>@endif @if($settings['show_maximum'])<td class="border-2 border-slate-900 p-2 text-center">{{number_format($mark['maximum'],1)}}</td>@endif @if($settings['show_percentage'])<td class="border-2 border-slate-900 p-2 text-center font-bold">{{number_format($mark['percentage'],1)}}%</td>@endif @if($settings['show_grade'])<td class="border-2 border-slate-900 p-2 text-center font-black">{{$mark['grade']}}</td>@endif @if($settings['show_points'])<td class="border-2 border-slate-900 p-2 text-center font-bold">{{$mark['points']??'—'}}</td>@endif @if($settings['show_remarks'])<td class="border-2 border-slate-900 p-2">{{$mark['comment']}}</td>@endif
                </tr>
                @empty<tr><td colspan="{{2 + ($settings['show_exam_breakdown'] ? $data['examColumns']->count() : 0) + collect(['show_marks','show_maximum','show_percentage','show_grade','show_points','show_remarks'])->filter(fn($key)=>$settings[$key])->count()}}" class="border-2 border-slate-900 p-6 text-center">No approved marks recorded.</td></tr>@endforelse
                </tbody>
            </table>

            <div class="mt-4 grid border-2 border-slate-900 text-center" style="grid-template-columns: repeat({{ 1 + (int) $settings['show_attendance'] + (int) $settings['show_points'] }}, minmax(0, 1fr))">
                @if($settings['show_attendance'])<div class="border-r-2 border-slate-900 p-2"><b class="block text-xs uppercase">Attendance</b><span class="text-lg font-black">{{$data['attendance_present']}}/{{$data['attendance_total']}}</span></div>@endif
                @if($settings['show_points'])<div class="border-r-2 border-slate-900 bg-yellow-50 p-2"><b class="block text-xs uppercase">Aggregate</b><span class="text-lg font-black">{{$data['aggregate']}}</span></div>@endif
                <div class="bg-yellow-50 p-2"><b class="block text-xs uppercase">Average</b><span class="text-lg font-black">{{number_format($data['average'],1)}}%</span></div>
            </div>

            @if($settings['show_position']||$settings['show_fees']||$settings['show_promotion'])
                <div class="mt-3 grid gap-2 text-center text-xs sm:grid-cols-3">
                    @if($settings['show_position'])<div class="border-2 border-slate-900 p-2"><b class="block uppercase">Position</b><span class="text-base font-black">{{$positions[$student->id]??'—'}}</span></div>@endif
                    @if($settings['show_fees'])<div class="border-2 border-slate-900 p-2"><b class="block uppercase">Fees balance</b><span class="text-base font-black">{{number_format($data['fees']['balance'])}}</span><small class="block text-slate-500">Paid {{number_format($data['fees']['paid'])}} / Due {{number_format($data['fees']['due'])}}</small></div>@endif
                    @if($settings['show_promotion'])<div class="border-2 border-slate-900 p-2"><b class="block uppercase">Promotion</b><span class="text-base font-black uppercase">{{$data['promotion']?:'Pending'}}</span></div>@endif
                </div>
            @endif

            <div class="mt-5 border-2 border-slate-900"><div class="bg-slate-900 p-2 text-xs font-black uppercase text-white">Remarks</div><div class="min-h-12 p-3 text-xs font-semibold">{{$data['teacher_remarks']}}</div></div>

            <div class="mt-auto pt-7">
                <div class="mb-5 grid grid-cols-3 gap-8 text-center text-xs font-bold"><div><div class="h-6 border-b-2 border-slate-900"></div>Teacher sign</div><div><div class="h-6 border-b-2 border-slate-900"></div>Parent sign</div><div><div class="h-6 border-b-2 border-slate-900"></div>Principal sign</div></div>
                <div class="border-2 border-slate-900 bg-slate-50 p-3 text-[10px] font-semibold italic"><div class="grid grid-cols-2 gap-x-8">@forelse($data['scales'] as $scale)<div><b class="not-italic">{{number_format($scale->minimum_percentage,0)}}–{{number_format($scale->maximum_percentage,0)}}%:</b> {{$scale->grade}} · {{$scale->remark}}</div>@empty<div class="col-span-2">No grading scale configured.</div>@endforelse</div>@if($settings['footer'])<p class="mt-3 border-t pt-2 text-center not-italic">{{$settings['footer']}}</p>@endif</div>
            </div>
        </article>
    @empty<div class="rounded-2xl border bg-white p-10 text-center text-slate-500">Select a term and class containing active learners.</div>@endforelse
</div>
