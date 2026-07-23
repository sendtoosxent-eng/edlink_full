<div class="space-y-6">
    <div class="rounded-2xl bg-gradient-to-r from-slate-900 to-slate-800 p-6 text-white">
        <p class="text-xs font-bold uppercase tracking-wider text-amber-300">Automatic academic progression</p>
        <h1 class="mt-2 text-2xl font-extrabold">Promotion preview and confirmation</h1>
        <p class="mt-2 max-w-3xl text-sm text-slate-300">Set the average pass mark. Edlink checks every active learner's approved term results, previews the decision, and only creates target-term enrolments after confirmation.</p>
    </div>

    @if(session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-800">{{session('status')}}</div>@endif
    @if(session('error'))<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm text-rose-800">{{session('error')}}</div>@endif

    <section class="rounded-2xl border bg-white p-6 shadow-sm">
        <div class="mb-5"><h2 class="font-bold">1. Evaluation parameters</h2><p class="text-xs text-slate-500">The source term must be closed. The target term must be pending or open.</p></div>
        <div class="grid gap-4 md:grid-cols-4 md:items-end">
            <label class="text-sm font-semibold">Closed source term<select wire:model.live="sourceTermId" class="mt-1 w-full rounded-xl border-slate-200"><option value="">Select term</option>@foreach($terms->where('status','closed') as $term)<option value="{{$term->id}}">{{$term->name}}, {{$term->year}}</option>@endforeach</select>@error('sourceTermId')<small class="text-rose-600">{{$message}}</small>@enderror</label>
            <label class="text-sm font-semibold">Target term<select wire:model.live="targetTermId" class="mt-1 w-full rounded-xl border-slate-200"><option value="">Select term</option>@foreach($terms->whereIn('status',['pending','open']) as $term)<option value="{{$term->id}}">{{$term->name}}, {{$term->year}} · {{ucfirst($term->status)}}</option>@endforeach</select>@error('targetTermId')<small class="text-rose-600">{{$message}}</small>@enderror</label>
            <label class="text-sm font-semibold">Average pass mark (%)<input wire:model.live.debounce.500ms="passMark" type="number" min="0" max="100" step="0.01" class="mt-1 w-full rounded-xl border-slate-200">@error('passMark')<small class="text-rose-600">{{$message}}</small>@enderror</label>
            <button wire:click="generatePreview" wire:loading.attr="disabled" wire:target="generatePreview" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-bold text-white disabled:opacity-50"><span wire:loading.remove wire:target="generatePreview">Evaluate all learners</span><span wire:loading wire:target="generatePreview">Calculating…</span></button>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border bg-white shadow-sm">
        <div class="flex items-center justify-between border-b p-5"><div><h2 class="font-bold">2. Automatic preview</h2><p class="text-xs text-slate-500">No enrolment changes are made during preview.</p></div>@if($previewReady)<div class="flex gap-2 text-xs font-bold"><span class="rounded-full bg-emerald-100 px-3 py-1 text-emerald-700">{{collect($preview)->where('outcome','promoted')->count()}} promoted</span><span class="rounded-full bg-amber-100 px-3 py-1 text-amber-700">{{collect($preview)->where('outcome','repeated')->count()}} repeat</span><span class="rounded-full bg-blue-100 px-3 py-1 text-blue-700">{{collect($preview)->where('outcome','graduated')->count()}} graduate</span></div>@endif</div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="p-4">Learner</th><th class="p-4">Current class</th><th class="p-4 text-center">Subjects</th><th class="p-4 text-center">Average</th><th class="p-4 text-center">Check</th><th class="p-4">Automatic outcome</th><th class="p-4">Target placement</th></tr></thead>
                <tbody class="divide-y">
                @forelse($preview as $row)
                    <tr><td class="p-4"><b>{{$row['student_name']}}</b><small class="block text-slate-400">{{$row['admission_no']}}</small></td><td class="p-4">{{$row['current_class']}}</td><td class="p-4 text-center">{{$row['subjects']}}</td><td class="p-4 text-center font-black">{{number_format($row['average'],1)}}%</td><td class="p-4 text-center">@if($row['subjects']===0)<span class="rounded-full bg-rose-100 px-2 py-1 text-xs font-bold text-rose-700">No marks</span>@elseif($row['passed'])<span class="rounded-full bg-emerald-100 px-2 py-1 text-xs font-bold text-emerald-700">Passed</span>@else<span class="rounded-full bg-amber-100 px-2 py-1 text-xs font-bold text-amber-700">Below {{$passMark}}%</span>@endif</td><td class="p-4 font-bold {{ $row['outcome']==='promoted'?'text-emerald-700':($row['outcome']==='graduated'?'text-blue-700':'text-amber-700') }}">{{ucfirst($row['outcome'])}}</td><td class="p-4">{{$row['target_class']}}</td></tr>
                @empty<tr><td colspan="7" class="p-12 text-center text-slate-500">Choose the terms and pass mark, then evaluate all learners.</td></tr>@endforelse
                </tbody>
            </table>
        </div>
        @if($previewReady && count($preview))
            <div class="flex flex-col gap-4 border-t bg-slate-50 p-5 sm:flex-row sm:items-center sm:justify-between"><p class="text-xs text-slate-500"><b>3. Confirmation:</b> This writes outcomes and creates or updates target-term enrolments. Learners without approved marks repeat automatically.</p><button wire:click="commit" wire:confirm="Confirm automatic promotion for all previewed learners? This will write target-term enrolments." wire:loading.attr="disabled" wire:target="commit" class="rounded-xl bg-amber-400 px-6 py-3 text-sm font-bold text-slate-950 disabled:opacity-50"><span wire:loading.remove wire:target="commit">Confirm and run promotions</span><span wire:loading wire:target="commit">Promoting…</span></button></div>
        @endif
    </section>
</div>
