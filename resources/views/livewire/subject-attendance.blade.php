<div class="mx-auto max-w-6xl space-y-6 text-slate-800">
    <!-- HEADER SECTION -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded-md bg-amber-100 px-2.5 py-0.5 text-xs font-semibold text-amber-800">
                    Teacher Workspace
                </span>
                <span class="text-xs text-slate-400">•</span>
                <span class="text-xs font-medium text-slate-500">{{ now()->format('l, d M Y') }}</span>
            </div>
            <h1 class="mt-1 text-2xl font-black tracking-tight text-slate-900 sm:text-3xl">
                Subject Attendance
            </h1>
        </div>

        @if ($term)
            <div class="inline-flex items-center gap-2.5 rounded-xl border border-slate-200 bg-white px-4 py-2.5 shadow-2xs">
                <span class="flex h-2 w-2 rounded-full bg-emerald-500"></span>
                <div class="text-xs">
                    <span class="text-slate-400 block font-medium uppercase tracking-wider text-[10px]">Active Term</span>
                    <strong class="font-bold text-slate-900">{{ $term->name }}, {{ $term->year }}</strong>
                </div>
            </div>
        @endif
    </div>

    <!-- FLASH & ERROR ALERTS -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm font-semibold text-emerald-800 shadow-2xs">
            <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @error('slotId')
        <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-sm font-semibold text-rose-800 shadow-2xs">
            <svg class="h-5 w-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ $message }}</span>
        </div>
    @enderror

    <!-- TERM CLOSED GUARD -->
    @if (! $term?->isOpen())
        <div class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center shadow-2xs">
            <div class="rounded-full bg-slate-100 p-3 text-slate-400">
                <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                </svg>
            </div>
            <h2 class="mt-4 text-base font-bold text-slate-900">No Open Term Active</h2>
            <p class="mt-1 max-w-sm text-xs text-slate-500">Subject attendance will become available once school administration officially opens the academic term.</p>
        </div>
    @else
        <!-- LESSON SELECTOR CARD -->
        <section class="relative rounded-2xl border border-slate-200 bg-white p-5 shadow-2xs">
            <label for="lesson" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-500">
                Select Today's Lesson
            </label>
            <div class="relative">
                <select id="lesson" wire:model.live="slotId" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 py-3 pl-4 pr-10 text-sm font-semibold text-slate-900 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                    <option value="">-- Choose a timetable slot --</option>
                    @foreach ($lessons as $item)
                        <option value="{{ $item->id }}">
                            {{ substr($item->starts_at, 0, 5) }} - {{ substr($item->ends_at, 0, 5) }} &bull; {{ $item->subject_name }} &bull; {{ $item->class_name }}{{ $item->stream_name ? ' ('.$item->stream_name.')' : '' }}
                        </option>
                    @endforeach
                </select>
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-400">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>
            </div>

            @if ($lessons->isEmpty())
                <div class="mt-4 flex items-start gap-3 rounded-xl border border-amber-200/80 bg-amber-50/60 p-4 text-xs text-amber-900">
                    <svg class="h-5 w-5 shrink-0 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <div>
                        <strong class="font-bold">No eligible lessons today.</strong>
                        <p class="mt-0.5 text-amber-800/80">Make sure your timetable slot lists you as the assigned teacher and aligns with your subject/class mapping for this term.</p>
                    </div>
                </div>
            @endif
        </section>
    @endif

    <!-- ATTENDANCE REGISTER & METRICS -->
    @if ($lesson)
        @php
            $present = collect($statuses)->filter(fn ($status) => $status === 'present')->count();
            $late    = collect($statuses)->filter(fn ($status) => $status === 'late')->count();
            $absent  = collect($statuses)->filter(fn ($status) => $status === 'absent')->count();
            $excused = collect($statuses)->filter(fn ($status) => $status === 'excused')->count();
        @endphp

        <!-- STATS & OVERVIEW CARDS -->
        <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
            <!-- Active Lesson Info -->
            <div class="flex flex-col justify-between rounded-2xl bg-slate-900 p-4 text-white shadow-xs lg:col-span-1">
                <div>
                    <span class="text-[10px] font-bold uppercase tracking-widest text-amber-400">Current Session</span>
                    <p class="mt-1 text-base font-extrabold truncate">{{ $lesson->subject_name }}</p>
                    <p class="text-xs font-medium text-slate-300 mt-0.5">
                        {{ $lesson->class_name }}{{ $lesson->stream_name ? ' · '.$lesson->stream_name : '' }}
                    </p>
                </div>
                <div class="mt-3 flex items-center gap-1.5 text-xs font-mono text-slate-400 border-t border-slate-800 pt-2">
                    <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>{{ substr($lesson->starts_at, 0, 5) }} - {{ substr($lesson->ends_at, 0, 5) }}</span>
                </div>
            </div>

            <!-- Present Badge -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Present</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-3xl font-black text-emerald-600">{{ $present }}</span>
                    <span class="rounded-md bg-emerald-50 px-2 py-0.5 text-[10px] font-bold text-emerald-700">Ready</span>
                </div>
            </div>

            <!-- Late Badge -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Late</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-3xl font-black text-amber-600">{{ $late }}</span>
                    <span class="rounded-md bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">Delayed</span>
                </div>
            </div>

            <!-- Absent Badge -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Absent</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-3xl font-black text-rose-600">{{ $absent }}</span>
                    <span class="rounded-md bg-rose-50 px-2 py-0.5 text-[10px] font-bold text-rose-700">Missing</span>
                </div>
            </div>

            <!-- Excused Badge -->
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-2xs flex flex-col justify-between">
                <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Excused</span>
                <div class="mt-2 flex items-baseline justify-between">
                    <span class="text-3xl font-black text-slate-600">{{ $excused }}</span>
                    <span class="rounded-md bg-slate-100 px-2 py-0.5 text-[10px] font-bold text-slate-600">Noted</span>
                </div>
            </div>
        </div>

        <!-- FORM & REGISTER TABLE -->
        <form wire:submit="save" class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
            <header class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-base font-bold text-slate-900">Class Register</h2>
                    <p class="text-xs text-slate-500">
                        {{ $saved ? $saved.' saved entries loaded. Make adjustments as needed.' : 'Default is set to Present. Record exceptions below.' }}
                    </p>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" wire:click="markAll('present')" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-200 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-700 hover:bg-emerald-100 transition active:scale-95">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        All Present
                    </button>
                    <button type="button" wire:click="markAll('absent')" class="inline-flex items-center gap-1.5 rounded-xl border border-rose-200 bg-rose-50 px-3 py-1.5 text-xs font-bold text-rose-700 hover:bg-rose-100 transition active:scale-95">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                        All Absent
                    </button>
                </div>
            </header>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        <tr>
                            <th class="px-5 py-3.5">Learner Name</th>
                            <th class="px-5 py-3.5">Admission No.</th>
                            <th class="px-5 py-3.5 text-right sm:text-left">Attendance Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($students as $student)
                            @php $st = $statuses[$student->id] ?? 'present'; @endphp
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="px-5 py-3.5 font-bold text-slate-900">
                                    {{ $student->name }}
                                </td>
                                <td class="px-5 py-3.5 font-mono text-slate-500">
                                    {{ $student->admission_no ?: '—' }}
                                </td>
                                <td class="px-5 py-2 text-right sm:text-left">
                                    <div class="inline-flex flex-wrap rounded-xl bg-slate-100 p-1 gap-0.5">
                                        <!-- Present Chip -->
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="statuses.{{ $student->id }}" value="present" class="sr-only peer">
                                            <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition peer-checked:bg-emerald-600 peer-checked:text-white peer-checked:shadow-2xs">
                                                Present
                                            </span>
                                        </label>

                                        <!-- Late Chip -->
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="statuses.{{ $student->id }}" value="late" class="sr-only peer">
                                            <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition peer-checked:bg-amber-500 peer-checked:text-white peer-checked:shadow-2xs">
                                                Late
                                            </span>
                                        </label>

                                        <!-- Absent Chip -->
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="statuses.{{ $student->id }}" value="absent" class="sr-only peer">
                                            <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition peer-checked:bg-rose-600 peer-checked:text-white peer-checked:shadow-2xs">
                                                Absent
                                            </span>
                                        </label>

                                        <!-- Excused Chip -->
                                        <label class="cursor-pointer">
                                            <input type="radio" wire:model.live="statuses.{{ $student->id }}" value="excused" class="sr-only peer">
                                            <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition peer-checked:bg-slate-700 peer-checked:text-white peer-checked:shadow-2xs">
                                                Excused
                                            </span>
                                        </label>
                                    </div>
                                    @error('statuses.'.$student->id)
                                        <p class="mt-1 text-[10px] font-semibold text-rose-600">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-slate-700">No active learners assigned</p>
                                    <p class="text-[11px] text-slate-400">No students are currently enrolled in this class and stream.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($students->isNotEmpty())
                <footer class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <p class="text-xs text-slate-500">
                        Submitting updates the register for this lesson slot without duplicating records.
                    </p>
                    <button type="submit" wire:loading.attr="disabled" class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400/50 disabled:opacity-50">
                        <svg wire:loading.remove wire:target="save" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                        </svg>
                        <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>{{ $saved ? 'Update Attendance' : 'Save Attendance' }}</span>
                    </button>
                </footer>
            @endif
        </form>
    @endif
</div>