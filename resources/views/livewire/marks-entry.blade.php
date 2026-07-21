<div class="space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div><p class="text-sm font-semibold text-yellow-600">Academics</p><h1 class="text-2xl font-bold">Marks entry</h1><p class="mt-1 text-sm text-slate-500">Enter, review and submit learner scores for academic approval.</p></div>
        @if ($term)<div class="rounded-xl border bg-white px-4 py-3 text-sm"><span class="text-slate-500">Current term</span><strong class="ml-2">{{ $term->name }}</strong><span class="ml-2 rounded-full px-2 py-0.5 text-xs font-bold {{ $term->isOpen() ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">{{ $term->isOpen() ? 'Open' : 'Closed' }}</span></div>@endif
    </div>
    @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
    @if (! $term)
        <div class="rounded-2xl border border-dashed bg-white p-10 text-center"><h2 class="font-bold">No current term</h2><p class="mt-2 text-sm text-slate-500">Open a school term before entering examination marks.</p></div>
    @else
        <div class="rounded-2xl border bg-white p-5 shadow-sm">
            <label for="paper" class="mb-2 block text-sm font-bold">Examination paper</label>
            <select id="paper" wire:model.live="paperId" class="w-full rounded-xl border-slate-300 text-sm focus:border-yellow-400 focus:ring-yellow-400"><option value="">Choose an assigned paper</option>@foreach ($papers as $item)<option value="{{ $item->id }}">{{ $item->exam->name }} - {{ $item->exam->schoolClass->name }}{{ $item->exam->stream ? ' '.$item->exam->stream->name : '' }} - {{ $item->subject->name }}</option>@endforeach</select>
            @error('paperId')<p class="mt-2 text-sm font-medium text-rose-600">{{ $message }}</p>@enderror
            @if ($papers->isEmpty())<p class="mt-3 text-sm text-slate-500">No papers are assigned to you for the current term.</p>@endif
        </div>
    @endif
    @if ($paper)
        @php
            $completed = collect($scores)->filter(fn ($score) => $score !== null && $score !== '')->count();
            $progress = $students->count() ? round(($completed / $students->count()) * 100) : 0;
            $statusStyle = match ($paperStatus) { 'approved' => 'bg-emerald-100 text-emerald-700', 'submitted' => 'bg-blue-100 text-blue-700', default => 'bg-amber-100 text-amber-700' };
        @endphp
        <div class="grid gap-4 md:grid-cols-4">
            <div class="rounded-2xl border bg-white p-4 md:col-span-2"><p class="text-xs font-bold uppercase text-slate-400">Paper</p><p class="mt-1 font-bold">{{ $paper->exam->name }} · {{ $paper->subject->name }}</p><p class="mt-1 text-sm text-slate-500">{{ $paper->exam->schoolClass->name }}{{ $paper->exam->stream ? ' · '.$paper->exam->stream->name : ' · All streams' }}</p></div>
            <div class="rounded-2xl border bg-white p-4"><p class="text-xs font-bold uppercase text-slate-400">Status</p><span class="mt-2 inline-flex rounded-full px-3 py-1 text-xs font-bold uppercase {{ $statusStyle }}">{{ $paperStatus }}</span></div>
            <div class="rounded-2xl border bg-white p-4"><p class="text-xs font-bold uppercase text-slate-400">Progress</p><p class="mt-1 text-xl font-bold">{{ $completed }}/{{ $students->count() }}</p><div class="mt-2 h-1.5 rounded-full bg-slate-100"><div class="h-full rounded-full bg-yellow-400" style="width: {{ $progress }}%"></div></div></div>
        </div>
        <form wire:submit="saveDraft" class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="px-5 py-4">Learner</th><th class="px-5 py-4">Admission number</th><th class="px-5 py-4 text-right">Score / {{ number_format($paper->maximum_score, 0) }}</th></tr></thead><tbody>
            @forelse ($students as $student)
                <tr class="border-t"><td class="px-5 py-4 font-semibold">{{ $student->name }}</td><td class="px-5 py-4 text-slate-500">{{ $student->admission_no ?: '—' }}</td><td class="px-5 py-3 text-right"><input wire:model.blur="scores.{{ $student->id }}" type="number" min="0" max="{{ $paper->maximum_score }}" step="0.01" class="w-28 rounded-lg border-slate-300 text-right font-semibold" @disabled($paperStatus === 'approved' || ! $term->isOpen())>@error('scores.'.$student->id)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td></tr>
            @empty<tr><td colspan="3" class="px-5 py-12 text-center text-slate-500">No active learners are enrolled for this paper.</td></tr>@endforelse
            </tbody></table></div>
            <div class="flex flex-wrap items-center justify-between gap-3 border-t bg-slate-50 px-5 py-4"><p class="text-xs text-slate-500">Drafts may be incomplete. Submission requires every learner's score.</p><div class="flex flex-wrap gap-2">
                @if ($paperStatus !== 'approved' && $term->isOpen() && $students->isNotEmpty())<button type="submit" class="rounded-xl border bg-white px-4 py-2 text-sm font-bold">Save draft</button><button type="button" wire:click="submitForApproval" class="rounded-xl bg-yellow-400 px-4 py-2 text-sm font-bold">Submit for approval</button>@endif
                @if ($canManage && $paperStatus === 'submitted' && $term->isOpen())<button type="button" wire:click="approve" class="rounded-xl bg-emerald-600 px-4 py-2 text-sm font-bold text-white">Approve marks</button>@endif
                @if ($canManage && $paperStatus === 'approved' && $term->isOpen())<button type="button" wire:click="reopen" wire:confirm="Reopen this paper for editing?" class="rounded-xl border border-rose-300 bg-white px-4 py-2 text-sm font-bold text-rose-600">Reopen paper</button>@endif
            </div></div>
        </form>
    @endif
</div>
