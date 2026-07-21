<div class="mx-auto max-w-6xl space-y-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"><div><p class="text-sm font-semibold text-yellow-600">Teacher workspace</p><h1 class="text-2xl font-bold">Subject attendance</h1><p class="mt-1 text-sm text-slate-500">{{ now()->format('l, d M Y') }} · Choose one of your assigned lessons for today.</p></div>@if ($term)<div class="rounded-xl border bg-white px-4 py-3 text-sm"><span class="text-slate-500">Term</span><strong class="ml-2">{{ $term->name }}, {{ $term->year }}</strong></div>@endif</div>

    @if (session('status'))<div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-medium text-emerald-800">{{ session('status') }}</div>@endif
    @error('slotId')<div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-sm font-medium text-rose-700">{{ $message }}</div>@enderror

    @if (! $term?->isOpen())
        <div class="rounded-2xl border border-dashed bg-white p-10 text-center"><h2 class="font-bold">No open term</h2><p class="mt-2 text-sm text-slate-500">Subject attendance becomes available when the school opens a term.</p></div>
    @else
        <section class="rounded-2xl border bg-white p-5 shadow-sm"><label for="lesson" class="mb-2 block text-sm font-bold">Today's assigned lesson</label><select id="lesson" wire:model.live="slotId" class="w-full rounded-xl border-slate-300 text-sm focus:border-yellow-400 focus:ring-yellow-400"><option value="">Choose a timetable lesson</option>@foreach ($lessons as $item)<option value="{{ $item->id }}">{{ substr($item->starts_at, 0, 5) }}-{{ substr($item->ends_at, 0, 5) }} · {{ $item->subject_name }} · {{ $item->class_name }}{{ $item->stream_name ? ' '.$item->stream_name : '' }}</option>@endforeach</select>@if ($lessons->isEmpty())<div class="mt-4 rounded-xl bg-amber-50 p-4 text-sm text-amber-800"><strong>No eligible lesson today.</strong> The timetable slot must name you as teacher and match your current-term subject/class assignment.</div>@endif</section>
    @endif

    @if ($lesson)
        @php
            $present = collect($statuses)->filter(fn ($status) => $status === 'present')->count();
            $late = collect($statuses)->filter(fn ($status) => $status === 'late')->count();
            $absent = collect($statuses)->filter(fn ($status) => $status === 'absent')->count();
            $excused = collect($statuses)->filter(fn ($status) => $status === 'excused')->count();
        @endphp
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <div class="rounded-2xl border bg-slate-900 p-4 text-white lg:col-span-1"><p class="text-xs font-bold text-slate-400">LESSON</p><p class="mt-1 font-bold">{{ $lesson->subject_name }}</p><p class="text-xs text-slate-300">{{ $lesson->class_name }}{{ $lesson->stream_name ? ' · '.$lesson->stream_name : '' }}<br>{{ substr($lesson->starts_at, 0, 5) }}-{{ substr($lesson->ends_at, 0, 5) }}</p></div>
            <div class="rounded-2xl border bg-white p-4"><p class="text-xs font-bold text-slate-400">PRESENT</p><p class="mt-1 text-2xl font-bold text-emerald-600">{{ $present }}</p></div>
            <div class="rounded-2xl border bg-white p-4"><p class="text-xs font-bold text-slate-400">LATE</p><p class="mt-1 text-2xl font-bold text-amber-600">{{ $late }}</p></div>
            <div class="rounded-2xl border bg-white p-4"><p class="text-xs font-bold text-slate-400">ABSENT</p><p class="mt-1 text-2xl font-bold text-rose-600">{{ $absent }}</p></div>
            <div class="rounded-2xl border bg-white p-4"><p class="text-xs font-bold text-slate-400">EXCUSED</p><p class="mt-1 text-2xl font-bold text-slate-600">{{ $excused }}</p></div>
        </div>

        <form wire:submit="save" class="overflow-hidden rounded-2xl border bg-white shadow-sm">
            <header class="flex flex-wrap items-center justify-between gap-3 border-b px-5 py-4"><div><h2 class="font-bold">Class register</h2><p class="text-xs text-slate-500">{{ $saved ? $saved.' saved entries loaded for editing.' : 'All learners start as present. Record exceptions below.' }}</p></div><div class="flex flex-wrap gap-2"><button type="button" wire:click="markAll('present')" class="rounded-lg border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700">All present</button><button type="button" wire:click="markAll('absent')" class="rounded-lg border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700">All absent</button></div></header>
            <div class="overflow-x-auto"><table class="w-full text-left text-sm"><thead class="bg-slate-50 text-xs uppercase tracking-wide text-slate-500"><tr><th class="px-5 py-4">Learner</th><th class="px-5 py-4">Admission number</th><th class="px-5 py-4">Attendance status</th></tr></thead><tbody class="divide-y">
                @forelse ($students as $student)<tr><td class="px-5 py-4 font-semibold">{{ $student->name }}</td><td class="px-5 py-4 text-slate-500">{{ $student->admission_no ?: 'â€”' }}</td><td class="px-5 py-3"><select wire:model.live="statuses.{{ $student->id }}" class="rounded-lg border-slate-300 text-sm"><option value="present">Present</option><option value="late">Late</option><option value="absent">Absent</option><option value="excused">Excused</option></select>@error('statuses.'.$student->id)<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</td></tr>@empty<tr><td colspan="3" class="px-5 py-12 text-center text-slate-500">No active learners are assigned to this class and stream.</td></tr>@endforelse
            </tbody></table></div>
            @if ($students->isNotEmpty())<footer class="flex flex-wrap items-center justify-between gap-3 border-t bg-slate-50 px-5 py-4"><p class="text-xs text-slate-500">Saving again updates this lesson's register; it does not create duplicates.</p><button class="rounded-xl bg-yellow-400 px-5 py-2.5 text-sm font-bold">{{ $saved ? 'Update attendance' : 'Save attendance' }}</button></footer>@endif
        </form>
    @endif
</div>

