<div class="mx-auto max-w-7xl space-y-6 [font-family:'Poppins',sans-serif]">

    <!-- HEADER BLOCK (DARK SLATE WITH YELLOW RING) -->
    <div class="bg-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm border border-slate-800 ring-2 ring-yellow-400">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-md text-xs font-bold tracking-wide bg-yellow-400/10 text-yellow-400 border border-yellow-400/20">
                        ATTENDANCE
                    </span>
                    @if($term)
                        <span class="text-xs font-medium text-slate-400">
                            • {{ $term->name }}, {{ $term->year }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-white mt-2">
                    Attendance Reports
                </h1>
                <p class="text-xs sm:text-sm font-normal text-slate-300 mt-1 max-w-2xl leading-relaxed">
                    Review daily and subject attendance records across classes and streams.
                </p>
            </div>

            <!-- ACTION BUTTONS -->
            <div class="flex items-center gap-3 shrink-0">
                <a wire:navigate href="{{ route('attendance.index') }}" 
                   class="inline-flex items-center gap-2 rounded-xl bg-slate-800 hover:bg-slate-700 border border-slate-700 px-4 py-2.5 text-xs font-bold text-white transition-all active:scale-95 shadow-xs">
                    <svg class="w-4 h-4 text-yellow-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                    </svg>
                    Daily Register
                </a>
                <button wire:click="exportCsv" 
                        class="inline-flex items-center gap-2 rounded-xl bg-yellow-400 hover:bg-yellow-300 text-slate-950 px-4 py-2.5 text-xs font-extrabold transition-all active:scale-95 shadow-xs cursor-pointer">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                    </svg>
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <!-- FILTERS SECTION -->
    <section class="rounded-2xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-xs space-y-4">
        <div class="flex items-center justify-between pb-3 border-b border-slate-100">
            <h2 class="text-xs font-bold text-slate-700 uppercase tracking-wider flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z" />
                </svg>
                Filter Records
            </h2>
            <button type="button" wire:click="clearFilters" 
                    class="text-xs font-bold text-slate-500 hover:text-slate-900 transition flex items-center gap-1 cursor-pointer">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                </svg>
                Reset Filters
            </button>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <!-- From Date -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">From Date</label>
                <input wire:model.live="fromDate" type="date" 
                       class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
            </div>

            <!-- To Date -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">To Date</label>
                <input wire:model.live="toDate" type="date" 
                       class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
            </div>

            <!-- Class -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Class</label>
                <select wire:model.live="schoolClassId" 
                        class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                    <option value="">All classes</option>
                    @foreach ($classes as $class)
                        <option value="{{ $class->id }}">{{ $class->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Stream -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Stream</label>
                <select wire:model.live="streamId" 
                        class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                    <option value="">All streams</option>
                    @foreach ($streams as $stream)
                        <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Subject / Session -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Subject / Session</label>
                <select wire:model.live="subjectId" 
                        class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                    <option value="all">All sessions</option>
                    <option value="daily">Daily register</option>
                    @foreach ($subjects as $subject)
                        <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- Status -->
            <div>
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Status</label>
                <select wire:model.live="status" 
                        class="w-full text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition">
                    <option value="all">All statuses</option>
                    <option value="present">Present</option>
                    <option value="late">Late</option>
                    <option value="absent">Absent</option>
                    <option value="excused">Excused</option>
                </select>
            </div>

            <!-- Find Learner Search Input -->
            <div class="xl:col-span-2">
                <label class="block text-xs font-semibold text-slate-600 tracking-wide mb-1.5">Find Learner</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" 
                           placeholder="Search name or admission number..." 
                           class="w-full pl-10 pr-4 py-2.5 text-xs font-medium bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition text-slate-900 placeholder:text-slate-400">
                </div>
            </div>
        </div>
    </section>

    <!-- METRICS CARDS -->
    <div class="grid grid-cols-2 gap-3 lg:grid-cols-5">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-4 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">RECORDS</p>
            <p class="mt-1 text-2xl font-extrabold text-slate-900">{{ number_format($total) }}</p>
        </div>

        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/40 p-4 shadow-xs">
            <p class="text-[10px] font-bold text-emerald-700 uppercase tracking-wider">PRESENT</p>
            <p class="mt-1 text-2xl font-extrabold text-emerald-600">{{ number_format($counts['present'] ?? 0) }}</p>
        </div>

        <div class="rounded-2xl border border-amber-100 bg-amber-50/40 p-4 shadow-xs">
            <p class="text-[10px] font-bold text-amber-700 uppercase tracking-wider">LATE</p>
            <p class="mt-1 text-2xl font-extrabold text-amber-600">{{ number_format($counts['late'] ?? 0) }}</p>
        </div>

        <div class="rounded-2xl border border-rose-100 bg-rose-50/40 p-4 shadow-xs">
            <p class="text-[10px] font-bold text-rose-700 uppercase tracking-wider">ABSENT</p>
            <p class="mt-1 text-2xl font-extrabold text-rose-600">{{ number_format($counts['absent'] ?? 0) }}</p>
        </div>

        <div class="col-span-2 rounded-2xl border border-slate-200/80 bg-white p-4 lg:col-span-1 shadow-xs">
            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ATTENDANCE RATE</p>
            <p class="mt-1 text-2xl font-extrabold {{ $rate >= 80 ? 'text-emerald-600' : 'text-rose-600' }}">
                {{ number_format($rate, 1) }}%
            </p>
        </div>
    </div>

    <!-- TABLE BLOCK (SHOWING 20 RESULTS PER PAGE) -->
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <header class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-sm">Attendance Records</h2>
                <p class="text-xs text-slate-400 mt-0.5">Showing 20 results per page. Present and late count toward rate.</p>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-lg">
                20 / page
            </span>
        </header>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                    <tr>
                        <th class="px-5 py-3.5">Date / time</th>
                        <th class="px-5 py-3.5">Subject / session</th>
                        <th class="px-5 py-3.5">Learner</th>
                        <th class="px-5 py-3.5">Class / stream</th>
                        <th class="px-5 py-3.5">Status</th>
                        <th class="px-5 py-3.5">Recorded by</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($records as $record)
                        @php 
                            $class = $record->schoolClass ?? $record->student?->schoolClass; 
                            $stream = $record->stream ?? $record->student?->stream; 
                        @endphp
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $record->attendance_date?->format('d M Y') }}</div>
                                <div class="text-[11px] text-slate-400 mt-0.5">
                                    {{ $record->lesson_time ? \Illuminate\Support\Carbon::parse($record->lesson_time)->format('g:i A') : 'Whole day' }}
                                </div>
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-md font-bold {{ $record->subject ? 'bg-blue-50 text-blue-700 border border-blue-100' : 'bg-slate-100 text-slate-600' }}">
                                    {{ $record->subject?->name ?? 'Daily register' }}
                                </span>
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <div class="font-bold text-slate-800">{{ $record->student?->name ?? 'Deleted learner' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $record->student?->admission_no }}</div>
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-600 font-medium">
                                {{ $class?->name ?? '—' }}{{ $stream ? ' · '.$stream->name : '' }}
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap">
                                <span class="inline-flex items-center px-3 py-1 rounded-full font-bold text-[11px] {{ match($record->status) { 'present' => 'bg-emerald-100/70 text-emerald-800', 'late' => 'bg-amber-100/70 text-amber-800', 'absent' => 'bg-rose-100/70 text-rose-800', default => 'bg-slate-100 text-slate-700' } }}">
                                    {{ ucfirst($record->status) }}
                                </span>
                            </td>

                            <td class="px-5 py-3.5 whitespace-nowrap text-slate-500 font-medium">
                                {{ $record->recorder?->name ?? 'System' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-5 py-12 text-center">
                                <p class="font-bold text-slate-700 text-sm">No attendance records found</p>
                                <p class="mt-1 text-xs text-slate-400">Try a wider date range or clear some filters.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($records->hasPages())
            <div class="border-t border-slate-100 px-5 py-4 bg-slate-50/50">
                {{ $records->links() }}
            </div>
        @endif
    </section>

</div>