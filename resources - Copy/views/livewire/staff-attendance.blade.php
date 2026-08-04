<div class="space-y-6">

    <!-- HEADER CARD BANNER -->
    <!-- HEADER BLOCK -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm mb-6">
    <div class="relative z-10 flex flex-col gap-5 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                Staff Attendance
            </h1>
            <p class="text-sm font-medium text-slate-400 mt-1 max-w-3xl">
                Mark daily staff attendance and review saved attendance history records.
            </p>
        </div>

        <!-- TAB TOGGLE -->
        <div class="inline-flex shrink-0 self-start sm:self-center rounded-xl bg-slate-950/60 p-1.5 border border-slate-700/50 backdrop-blur-sm">
            <button type="button" 
                    wire:click="setTab('mark')" 
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200 {{ $tab === 'mark' ? 'bg-amber-400 text-slate-950 shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                Mark Attendance
            </button>
            <button type="button" 
                    wire:click="setTab('history')" 
                    class="inline-flex items-center gap-2 rounded-lg px-4 py-2 text-xs font-bold transition-all duration-200 {{ $tab === 'history' ? 'bg-amber-400 text-slate-950 shadow-md' : 'text-slate-300 hover:text-white hover:bg-white/5' }}">
                <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                View History
            </button>
        </div>
    </div>

    <!-- Glowing Background Ambient Effect -->
    <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
</div>

    <!-- FLASH & ALERT NOTIFICATIONS -->
    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm font-semibold text-emerald-800 shadow-2xs">
            <svg class="h-5 w-5 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50/80 p-4 text-sm font-semibold text-rose-800 shadow-2xs">
            <svg class="h-5 w-5 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- TAB 1: MARK ATTENDANCE -->
    @if($tab === 'mark')
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
            
            <!-- CONTROL BAR: Date Selector & Quick Bulk Actions -->
            <div class="flex flex-col gap-4 border-b border-slate-100 bg-slate-50/50 p-5 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <label for="attendanceDate" class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-500">
                        Attendance Date
                    </label>
                    <div class="relative inline-block">
                        <input id="attendanceDate" 
                               type="date" 
                               max="{{ now()->toDateString() }}" 
                               wire:model.live="attendanceDate" 
                               class="rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                    </div>
                    @error('attendanceDate') 
                        <span class="mt-1 block text-[11px] font-semibold text-rose-600">{{ $message }}</span> 
                    @enderror
                </div>

                <!-- BULK ACTIONS -->
                <div>
                    <span class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-slate-400">
                        Set All Staff To
                    </span>
                    <div class="flex flex-wrap items-center gap-1.5">
                        @foreach($statusesList as $status)
                            <button type="button" 
                                    wire:click="markAll('{{ $status }}')" 
                                    class="inline-flex items-center rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-100 hover:text-slate-900 active:scale-95 shadow-2xs">
                                All {{ str($status)->replace('_', ' ')->title() }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- DAILY COUNTS SUMMARY CARDS -->
            <div class="grid grid-cols-2 gap-3 bg-slate-50/80 p-5 border-b border-slate-100 sm:grid-cols-3 lg:grid-cols-5">
                @foreach($statusesList as $status)
                    @php
                        $colorClasses = match($status) {
                            'present'  => 'text-emerald-600 bg-emerald-50 border-emerald-100',
                            'absent'   => 'text-rose-600 bg-rose-50 border-rose-100',
                            'late'     => 'text-amber-600 bg-amber-50 border-amber-100',
                            'on_leave' => 'text-indigo-600 bg-indigo-50 border-indigo-100',
                            default    => 'text-slate-700 bg-white border-slate-200'
                        };
                    @endphp
                    <div class="flex flex-col justify-between rounded-xl border p-3.5 bg-white shadow-2xs">
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400">
                            {{ str($status)->replace('_', ' ')->title() }}
                        </span>
                        <div class="mt-2 flex items-baseline justify-between">
                            <span class="text-2xl font-black text-slate-900">{{ $dayCounts[$status] ?? 0 }}</span>
                            <span class="rounded-md border px-1.5 py-0.5 text-[10px] font-bold {{ $colorClasses }}">
                                Status
                            </span>
                        </div>
                    </div>
                @endforeach
            </div>

            <!-- FORM & TABLE -->
            <form wire:submit="save">
                <div class="max-h-[580px] overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 shadow-2xs">
                            <tr>
                                <th class="px-5 py-3.5">Staff Member</th>
                                <th class="px-5 py-3.5">Job Title / Role</th>
                                <th class="px-5 py-3.5">Attendance Status</th>
                                <th class="px-5 py-3.5">Notes</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($staff as $member)
                                <tr class="transition hover:bg-slate-50/80">
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-3">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-slate-200 font-bold text-slate-600 text-xs uppercase">
                                                {{ substr($member->name, 0, 2) }}
                                            </div>
                                            <div>
                                                <p class="font-bold text-slate-900">{{ $member->name }}</p>
                                                <p class="font-mono text-[11px] text-slate-400">
                                                    {{ $member->staff_number ?: 'No Staff #' }}
                                                </p>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-5 py-3.5 text-slate-600 font-medium">
                                        {{ $member->job_title ?: str($member->role)->replace('_', ' ')->title() }}
                                    </td>
                                    <td class="px-5 py-2">
                                        <div class="inline-flex flex-wrap rounded-xl bg-slate-100 p-1 gap-0.5">
                                            @foreach($statusesList as $statusOption)
                                                <label class="cursor-pointer">
                                                    <input type="radio" 
                                                           wire:model="statuses.{{ $member->id }}" 
                                                           value="{{ $statusOption }}" 
                                                           class="sr-only peer">
                                                    <span class="inline-block rounded-lg px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition peer-checked:bg-slate-900 peer-checked:text-white peer-checked:shadow-2xs">
                                                        {{ str($statusOption)->replace('_', ' ')->title() }}
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </td>
                                    <td class="px-5 py-2">
                                        <input type="text" 
                                               wire:model="notes.{{ $member->id }}" 
                                               maxlength="500" 
                                               placeholder="Optional remark or reason..." 
                                               class="w-full min-w-[200px] rounded-xl border border-slate-200 bg-slate-50/50 px-3 py-1.5 text-xs font-medium text-slate-800 transition focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-12 text-center">
                                        <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z" />
                                            </svg>
                                        </div>
                                        <p class="mt-2 text-xs font-semibold text-slate-700">No Active Staff Members Found</p>
                                        <p class="text-[11px] text-slate-400">There are currently no staff records available to mark attendance.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($canMark && $staff->isNotEmpty())
                    <footer class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p class="text-xs text-slate-500">
                            Submitting will update the attendance register for this specific date.
                        </p>
                        <button type="submit" 
                                wire:loading.attr="disabled" 
                                wire:target="save" 
                                class="inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-2.5 text-xs font-bold text-slate-950 transition hover:bg-amber-300 focus:outline-none focus:ring-2 focus:ring-amber-400/50 disabled:opacity-50 active:scale-95">
                            <svg wire:loading.remove wire:target="save" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" />
                            </svg>
                            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin text-slate-950" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            <span>Save Attendance</span>
                        </button>
                    </footer>
                @elseif(!$canMark)
                    <footer class="border-t border-slate-100 bg-slate-50/50 p-4 text-center text-xs font-semibold text-slate-500">
                        You have read-only access. Only system administrators can commit attendance records.
                    </footer>
                @endif
            </form>
        </div>

    <!-- TAB 2: VIEW ATTENDANCE HISTORY -->
    @else
        <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xs">
            
            <!-- SEARCH & FILTERS TOOLBAR -->
            <div class="grid gap-3 border-b border-slate-100 bg-slate-50/50 p-5 sm:grid-cols-2 lg:grid-cols-4">
                <!-- Search Staff -->
                <div class="relative">
                    <input type="search" 
                           wire:model.live.debounce.300ms="search" 
                           placeholder="Search staff name or number..." 
                           class="w-full rounded-xl border border-slate-200 bg-white pl-9 pr-3 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                    <svg class="pointer-events-none absolute left-3 top-2.5 h-4 w-4 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                    </svg>
                </div>

                <!-- Status Filter -->
                <select wire:model.live="statusFilter" class="rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                    <option value="">All Statuses</option>
                    @foreach($statusesList as $status)
                        <option value="{{ $status }}">{{ str($status)->replace('_', ' ')->title() }}</option>
                    @endforeach
                </select>

                <!-- Date From -->
                <div class="relative">
                    <input type="date" 
                           wire:model.live="dateFrom" 
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                </div>

                <!-- Date To -->
                <div class="relative">
                    <input type="date" 
                           wire:model.live="dateTo" 
                           class="w-full rounded-xl border border-slate-200 bg-white px-3 py-2 text-xs font-semibold text-slate-900 transition focus:border-amber-400 focus:outline-none focus:ring-2 focus:ring-amber-400/20">
                </div>
            </div>

            <!-- RECORDS TABLE -->
            <div class="max-h-[600px] overflow-x-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 z-10 border-b border-slate-200 bg-slate-50 text-[10px] font-bold uppercase tracking-wider text-slate-500 shadow-2xs">
                        <tr>
                            <th class="px-5 py-3.5">Date</th>
                            <th class="px-5 py-3.5">Staff Member</th>
                            <th class="px-5 py-3.5">Status</th>
                            <th class="px-5 py-3.5">Note</th>
                            <th class="px-5 py-3.5">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($records as $record)
                            <tr class="transition hover:bg-slate-50/80">
                                <td class="whitespace-nowrap px-5 py-3.5 font-mono text-slate-600 font-semibold">
                                    {{ $record->attendance_date->format('d M Y') }}
                                </td>
                                <td class="px-5 py-3.5">
                                    <p class="font-bold text-slate-900">{{ $record->staff?->name ?: 'Deleted Staff Member' }}</p>
                                    <p class="font-mono text-[11px] text-slate-400">{{ $record->staff?->staff_number ?: '—' }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    @php
                                        $badgeColor = match($record->status) {
                                            'present'  => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                            'absent'   => 'bg-rose-50 text-rose-700 border-rose-200',
                                            'late'     => 'bg-amber-50 text-amber-700 border-amber-200',
                                            'on_leave' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                            default    => 'bg-slate-100 text-slate-700 border-slate-200'
                                        };
                                    @endphp
                                    <span class="inline-flex rounded-md border px-2.5 py-0.5 text-[10px] font-bold {{ $badgeColor }}">
                                        {{ str($record->status)->replace('_', ' ')->title() }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 font-medium max-w-xs truncate">
                                    {{ $record->note ?: '—' }}
                                </td>
                                <td class="px-5 py-3.5 text-slate-500 font-medium">
                                    {{ $record->recorder?->name ?: 'System Auto' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-5 py-12 text-center">
                                    <div class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                                        </svg>
                                    </div>
                                    <p class="mt-2 text-xs font-semibold text-slate-700">No Records Found</p>
                                    <p class="text-[11px] text-slate-400">No staff attendance history matches your selected search criteria or date ranges.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            @if($records->hasPages())
                <div class="border-t border-slate-100 bg-slate-50/50 p-4">
                    {{ $records->links() }}
                </div>
            @endif
        </div>
    @endif
</div>