<div>
    <div class="mb-8"><h1 class="text-2xl font-bold">Exam results</h1><p class="text-slate-500">Calculated from approved papers only, for the current school and term.</p></div>
    @if(session('status'))<div class="mb-4 rounded-xl bg-emerald-50 p-3 text-emerald-700">{{ session('status') }}</div>@endif
    @if(session('error'))<div class="mb-4 rounded-xl bg-rose-50 p-3 text-rose-700">{{ session('error') }}</div>@endif
    <select wire:model.live="examId" class="mb-6 w-full max-w-xl rounded-xl border-slate-200"><option value="">Select exam</option>@foreach($exams as $item)<option value="{{ $item->id }}">{{ $item->name }} · {{ $item->schoolClass->name }}</option>@endforeach</select>
    @if($exam)
        <div class="mb-4 flex flex-wrap items-center gap-3">
            <span class="rounded-full px-3 py-1 text-sm font-bold {{ $exam->isPublished() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $exam->isPublished() ? 'Published to learners and parents' : 'Internal only' }}</span>
            @if($canManage && $exam->term->isOpen())
                @if($exam->isPublished())<button wire:click="unpublish" class="rounded-xl border px-4 py-2 text-sm font-bold">Unpublish</button>@else<button wire:click="publish" class="rounded-xl bg-yellow-400 px-4 py-2 text-sm font-bold">Publish results</button>@endif
            @endif
            <a href="{{ route('settings.result-access') }}" class="text-sm font-bold text-yellow-700">Result access rule</a>
        </div>
        <div class="overflow-x-auto rounded-2xl border bg-white"><table class="w-full text-sm"><thead class="bg-slate-50"><tr><th class="p-4 text-left">Student</th>@foreach($exam->papers as $paper)<th class="p-4 text-center">{{ $paper->subject->name }}</th>@endforeach<th class="p-4">Total</th><th class="p-4">Average</th><th class="p-4">Grade</th><th class="p-4">Position</th><th></th></tr></thead><tbody>@forelse($results as $result)<tr class="border-t"><td class="p-4 font-semibold">{{ $result['student']->name }}</td>@foreach($result['subjects'] as $subject)<td class="p-4 text-center">{{ $subject['score'] }}/{{ $subject['max'] }}<br><span class="text-xs text-slate-400">{{ $subject['grade'] }}</span></td>@endforeach<td class="p-4 text-center">{{ number_format($result['total'],1) }}</td><td class="p-4 text-center">{{ $result['percentage'] }}%</td><td class="p-4 text-center font-bold">{{ $result['grade'] }}</td><td class="p-4 text-center">{{ $result['position'] }}</td><td class="p-4"><a target="_blank" href="{{ route('exams.report-card',[$exam,$result['student']]) }}" class="font-bold text-yellow-700">Print</a></td></tr>@empty<tr><td colspan="11" class="p-8 text-center text-slate-400">No approved papers or learners available.</td></tr>@endforelse</tbody></table></div>
    @endif
</div>
