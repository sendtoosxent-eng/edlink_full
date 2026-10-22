<div class="space-y-6">

    <!-- HEADER BLOCK (DARK SLATE WITH YELLOW RING) -->
    <div class="bg-slate-900 rounded-2xl p-6 sm:p-8 text-white shadow-sm border border-slate-800">
        <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
            <div>
                <div class="flex items-center gap-2">
                    
                    @if($term)
                        <span class="text-xs font-medium text-slate-400">
                            • {{ $term->name }}, {{ $term->year }}
                        </span>
                    @endif
                </div>
                <h1 class="text-2xl font-bold tracking-tight text-amber-300">
                    Attendance Reports
                </h1>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">
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

    <!-- CLASS / GENDER SUMMARY -->
    <style>
        .attendance-sheet { border-collapse: separate; border-spacing: 0; font-variant-numeric: tabular-nums; }
        .attendance-sheet th, .attendance-sheet td { border-right: 1px solid #cbd5e1; border-bottom: 1px solid #cbd5e1; padding: 10px 12px; }
        .attendance-sheet thead th { background: #e2e8f0; color: #334155; text-align: center; }
        .attendance-sheet thead { position: sticky; top: 0; z-index: 3; }
        .attendance-sheet tr > :first-child { position: sticky; left: 0; }
        .attendance-sheet tbody tr > :first-child { background: #f8fafc; z-index: 1; }
        .attendance-sheet tbody tr:nth-child(even) { background: #f8fafc; }
        .attendance-sheet tbody tr:hover td { background: #eff6ff; }
        .attendance-sheet tfoot th, .attendance-sheet tfoot td { background: #e2e8f0; border-top: 2px solid #64748b; }
    </style>
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <header class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-900 text-sm">Attendance summary by class and gender</h2>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="mt-3 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                <span wire:loading.remove wire:target="exportExcel">Download Excel (.xlsx)</span>
                <span wire:loading wire:target="exportExcel">Preparing Excel…</span>
            </button>
            <p class="text-xs text-slate-500 mt-0.5">Attendance records matching the filters, not unique learners. Select one date and Daily register for a daily headcount. All sessions can count a learner more than once per day. Present and late are separate counts.</p>
            <p class="text-xs text-slate-500 mt-1">Unspecified gender includes records with missing or unrecognised gender. Unmarked learners are not counted as absent.</p>
        </header>

        <div class="overflow-auto max-h-[600px]" tabindex="0" role="region" aria-label="Attendance summary spreadsheet">
            <table class="attendance-sheet w-full min-w-[1100px] text-left text-xs">
                <thead class="bg-slate-50 text-[10px] uppercase tracking-wider text-slate-400 font-bold border-b border-slate-100">
                    <tr>
                        <th rowspan="2" class="px-5 py-3.5 align-bottom">Class</th>
                        <th colspan="5" class="px-5 py-2 text-center border-l border-slate-100">Boys</th>
                        <th colspan="5" class="px-5 py-2 text-center border-l border-slate-100">Girls</th>
                        <th rowspan="2" class="px-3 py-3.5 align-bottom">Unspecified gender</th>
                        <th rowspan="2" class="px-5 py-3.5 align-bottom border-l border-slate-100">Class total</th>
                    </tr>
                    <tr class="border-t border-slate-100">
                        @foreach (['Boys', 'Girls'] as $gender)
                            @foreach (['Present', 'Late', 'Absent', 'Excused', 'Total'] as $statusLabel)
                                <th class="px-3 py-2 {{ $statusLabel === 'Total' ? 'font-extrabold' : '' }}">{{ $statusLabel }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse ($attendanceSummary as $summary)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="px-5 py-3.5 whitespace-nowrap font-bold text-slate-800">{{ $summary['class'] }}</td>
                            @foreach (['male', 'female'] as $gender)
                                @php
                                    $group = $summary['groups'][$gender];
                                @endphp
                                <td class="px-3 py-3.5 text-center text-emerald-700">{{ number_format($group['present']) }}</td>
                                <td class="px-3 py-3.5 text-center text-amber-700">{{ number_format($group['late']) }}</td>
                                <td class="px-3 py-3.5 text-center text-rose-700">{{ number_format($group['absent']) }}</td>
                                <td class="px-3 py-3.5 text-center text-blue-700">{{ number_format($group['excused']) }}</td>
                                <td class="px-3 py-3.5 text-center font-extrabold text-slate-800">{{ number_format($group['total']) }}</td>
                            @endforeach
                            <td class="px-3 py-3.5 text-center">{{ number_format($summary['unspecified']) }}</td>
                            <td class="px-5 py-3.5 border-l border-slate-100 text-center font-extrabold text-slate-900">{{ number_format($summary['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="px-5 py-10 text-center text-xs text-slate-400">No attendance summary is available for the selected filters.</td></tr>
                    @endforelse
                </tbody>
                <tfoot class="bg-slate-100 font-bold text-slate-900">
                    <tr>
                        <th scope="row" class="px-5 py-3">Grand total</th>
                        @foreach (['male', 'female'] as $gender)
                            @foreach (['present', 'late', 'absent', 'excused', 'total'] as $summaryStatus)
                                <td class="px-3 py-3 text-center">{{ number_format($attendanceSummary->sum(fn ($row) => $row['groups'][$gender][$summaryStatus])) }}</td>
                            @endforeach
                        @endforeach
                        <td class="px-3 py-3 text-center">{{ number_format($attendanceSummary->sum('unspecified')) }}</td>
                        <td class="px-5 py-3 text-center">{{ number_format($attendanceSummary->sum('total')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-xs">
        <header class="border-b border-slate-100 px-5 py-4">
            <h2 class="font-bold text-slate-900 text-sm">Attendance by student category</h2>
            <p class="mt-1 text-xs text-slate-500">Uses the categories configured by your school and each learner’s current category. The same report filters apply. Categories with no matching records show zero.</p>
            <button type="button" wire:click="exportExcel" wire:loading.attr="disabled" wire:target="exportExcel" class="mt-3 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 disabled:opacity-50">
                <span wire:loading.remove wire:target="exportExcel">Download Excel (.xlsx)</span>
                <span wire:loading wire:target="exportExcel">Preparing Excel…</span>
            </button>
            <p class="mt-1 text-xs text-slate-500">Includes class and category summaries for the selected filters.</p>
        </header>
        <div class="overflow-auto max-h-[600px]" tabindex="0" role="region" aria-label="Attendance by category spreadsheet">
            <table class="attendance-sheet w-full min-w-[1100px] text-xs">
                <thead>
                    <tr>
                        <th rowspan="2" scope="col">Category</th>
                        <th colspan="5" scope="colgroup">Boys</th>
                        <th colspan="5" scope="colgroup">Girls</th>
                        <th rowspan="2" scope="col">Unspecified gender</th>
                        <th rowspan="2" scope="col">Category total</th>
                    </tr>
                    <tr>
                        @foreach (['male', 'female'] as $gender)
                            @foreach (['Present', 'Late', 'Absent', 'Excused', 'Total'] as $label)
                                <th scope="col">{{ $label }}</th>
                            @endforeach
                        @endforeach
                    </tr>
                </thead>
                <tbody>
                    @forelse ($categorySummary as $category)
                        <tr>
                            <th scope="row" class="text-left whitespace-nowrap text-slate-800">{{ $category['class'] }}</th>
                            @foreach (['male', 'female'] as $gender)
                                @foreach (['present', 'late', 'absent', 'excused', 'total'] as $statusKey)
                                    <td class="text-center {{ $statusKey === 'total' ? 'font-bold' : '' }}">{{ number_format($category['groups'][$gender][$statusKey]) }}</td>
                                @endforeach
                            @endforeach
                            <td class="text-center">{{ number_format($category['unspecified']) }}</td>
                            <td class="text-center font-bold">{{ number_format($category['total']) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="13" class="text-center text-slate-500">No student categories or matching attendance records.</td></tr>
                    @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <th scope="row" class="text-left">Grand total</th>
                        @foreach (['male', 'female'] as $gender)
                            @foreach (['present', 'late', 'absent', 'excused', 'total'] as $statusKey)
                                <td class="text-center font-bold">{{ number_format($categorySummary->sum(fn ($row) => $row['groups'][$gender][$statusKey])) }}</td>
                            @endforeach
                        @endforeach
                        <td class="text-center font-bold">{{ number_format($categorySummary->sum('unspecified')) }}</td>
                        <td class="text-center font-bold">{{ number_format($categorySummary->sum('total')) }}</td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    <!-- ATTENDANCE RECORDS -->
    <section class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <header class="border-b border-slate-100 px-5 py-4 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-sm">Attendance Records</h2>
                <p class="text-xs text-slate-400 mt-0.5">Showing 40 results per page. Present and late count toward rate.</p>
            </div>
            <span class="text-xs font-bold text-slate-600 bg-slate-100 px-3 py-1 rounded-lg">
                40 / page
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
