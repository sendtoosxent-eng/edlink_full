<div class="space-y-6">

    <!-- Top Header Banner -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Audit Trail
                </h1>
                <p class="mt-1 text-sm text-slate-400 max-w-xl">
                    Review page visits, Livewire actions, and important system operations performed by staff.
                </p>
            </div>

            <div class="flex items-center gap-3 self-start sm:self-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-500/10 border border-emerald-500/20 px-3.5 py-1.5 text-xs font-bold text-emerald-400 backdrop-blur-sm">
                    <span class="h-2 w-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    Audit Capture Active
                </span>
            </div>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Stat Cards Grid -->
    <div class="grid gap-4 sm:grid-cols-3">
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Activities Today</p>
            <p class="mt-2 text-3xl font-black text-slate-900">{{ number_format($todayCount) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Actions Today</p>
            <p class="mt-2 text-3xl font-black text-blue-600">{{ number_format($actionCount) }}</p>
        </div>
        <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-sm">
            <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Active Staff Today</p>
            <p class="mt-2 text-3xl font-black text-emerald-600">{{ number_format($activeUsers) }}</p>
        </div>
    </div>

    <!-- Filter Control Panel & Log Table -->
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        
        <!-- Filter Controls Bar -->
        <div class="grid gap-3.5 border-b border-slate-100 p-5 md:grid-cols-2 xl:grid-cols-4 bg-slate-50/50">
            
            <!-- Search Field -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Search Logs</label>
                <input wire:model.live.debounce.350ms="search" 
                       type="text" 
                       placeholder="Search event, staff, route or IP..." 
                       class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10 placeholder:text-slate-400">
            </div>

            <!-- Staff Select -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Staff Member</label>
                <div class="relative">
                    <select wire:model.live="userId" class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-3.5 pr-10 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="">All staff</option>
                        @foreach($users as $member)
                            <option value="{{ $member->id }}">{{ $member->name }} — {{ ucfirst(str_replace('_',' ',$member->role)) }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Role Select -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Role</label>
                <div class="relative">
                    <select wire:model.live="role" class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-3.5 pr-10 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="">All roles</option>
                        @foreach(['admin','superadmin','academic_admin','registrar','teacher','bursar'] as $staffRole)
                            <option value="{{ $staffRole }}">{{ ucfirst(str_replace('_',' ',$staffRole)) }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Event Select -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Event Type</label>
                <div class="relative">
                    <select wire:model.live="event" class="w-full appearance-none rounded-xl border border-slate-200 bg-white py-2.5 pl-3.5 pr-10 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="">All events</option>
                        @foreach($events as $eventName)
                            <option value="{{ $eventName }}">{{ $eventName }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Date Range Filters -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">From Date</label>
                <input wire:model.live="fromDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
            </div>

            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">To Date</label>
                <input wire:model.live="toDate" type="date" class="w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
            </div>

            <!-- Clear Action -->
            <div class="flex items-end xl:col-span-2">
                <button wire:click="clearFilters" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-bold text-slate-600 transition duration-200 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200">
                    Clear Filters
                </button>
            </div>
        </div>

        <!-- Audit Table -->
        <div class="overflow-x-auto">
            <table class="w-full min-w-[980px] text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs font-bold uppercase tracking-wider text-slate-500 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-3.5">Date &amp; Time</th>
                        <th class="px-6 py-3.5">Staff Member</th>
                        <th class="px-6 py-3.5">Event</th>
                        <th class="px-6 py-3.5">Location / Action</th>
                        <th class="px-6 py-3.5">IP Address</th>
                        <th class="px-6 py-3.5 text-right">Action</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($logs as $log)
                        @php($meta = $log->metadata ?? [])
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            <td class="whitespace-nowrap px-6 py-4">
                                <b class="text-slate-900 font-bold">{{ $log->created_at->format('d M Y') }}</b>
                                <span class="block text-xs font-normal text-slate-400 mt-0.5">{{ $log->created_at->format('h:i:s A') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <b class="text-slate-900 font-bold block">{{ $log->user?->name ?: 'System' }}</b>
                                <span class="block text-xs font-normal capitalize text-slate-400 mt-0.5">{{ str_replace('_',' ',$log->user?->role ?: 'system') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-bold
                                    {{ $log->event==='livewire.action' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' : '' }}
                                    {{ str_contains($log->event,'deleted') ? 'bg-rose-50 text-rose-700 ring-1 ring-rose-600/20' : '' }}
                                    {{ str_contains($log->event,'recorded') || str_contains($log->event,'created') ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : '' }}
                                    {{ !in_array($log->event, ['livewire.action']) && !str_contains($log->event,'deleted') && !str_contains($log->event,'recorded') && !str_contains($log->event,'created') ? 'bg-slate-100 text-slate-700' : '' }}">
                                    <span class="h-1.5 w-1.5 rounded-full 
                                        {{ $log->event==='livewire.action' ? 'bg-blue-500' : '' }}
                                        {{ str_contains($log->event,'deleted') ? 'bg-rose-500' : '' }}
                                        {{ str_contains($log->event,'recorded') || str_contains($log->event,'created') ? 'bg-emerald-500' : '' }}
                                        {{ !in_array($log->event, ['livewire.action']) && !str_contains($log->event,'deleted') && !str_contains($log->event,'recorded') && !str_contains($log->event,'created') ? 'bg-slate-400' : '' }}"></span>
                                    {{ $log->event }}
                                </span>
                            </td>
                            <td class="max-w-xs px-6 py-4">
                                <b class="block truncate font-bold text-slate-800">{{ data_get($meta,'action') ?: data_get($meta,'route') ?: class_basename($log->subject_type ?: '') ?: '—' }}</b>
                                <span class="block truncate text-xs font-normal text-slate-400 mt-0.5" title="{{ data_get($meta,'component') ?: data_get($meta,'path') ?: ($log->subject_type ? $log->subject_type.' #'.$log->subject_id : 'No additional location') }}">
                                    {{ data_get($meta,'component') ?: data_get($meta,'path') ?: ($log->subject_type ? $log->subject_type.' #'.$log->subject_id : 'No additional location') }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 font-mono text-xs text-slate-500">
                                {{ $log->ip_address ?: '—' }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                <button wire:click="showDetails({{ $log->id }})" class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 shadow-sm transition duration-150 hover:bg-slate-900 hover:text-white hover:border-slate-900">
                                    Details
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-14 text-center text-slate-400">
                                <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                No audit activity matches these filters. Clear the filters or perform a staff action and return here.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="border-t border-slate-100 p-4 bg-slate-50/50">
            {{ $logs->links() }}
        </div>
    </section>

    <!-- Details Modal -->
    @if($selectedLog)
        <div class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/60 p-4 backdrop-blur-sm" wire:click.self="closeDetails">
            <div class="max-h-[90vh] w-full max-w-2xl overflow-y-auto rounded-3xl bg-white p-6 sm:p-8 shadow-2xl border border-slate-100">
                <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                    <div>
                        <span class="inline-block rounded-lg bg-amber-100/80 px-2.5 py-1 text-xs font-bold text-amber-800 mb-2">Audit Entry</span>
                        <h2 class="text-xl font-extrabold text-slate-900">Activity Details</h2>
                        <p class="mt-0.5 text-xs text-slate-500">Record #{{ $selectedLog->id }} · {{ $selectedLog->created_at->format('d M Y, h:i:s A') }}</p>
                    </div>
                    <button wire:click="closeDetails" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">&times;</button>
                </div>

                <div class="mt-5 grid gap-3 sm:grid-cols-2">
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Staff Member</span>
                        <p class="mt-1 font-bold text-slate-900">{{ $selectedLog->user?->name ?: 'System' }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $selectedLog->user?->email }} · {{ ucfirst(str_replace('_',' ',$selectedLog->user?->role ?: 'system')) }}</p>
                    </div>
                    <div class="rounded-2xl bg-slate-50 p-4 border border-slate-100">
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Event</span>
                        <p class="mt-1 font-bold text-slate-900">{{ $selectedLog->event }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">IP: {{ $selectedLog->ip_address ?: 'Not available' }}</p>
                    </div>
                </div>

                <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200/80 bg-slate-50/50">
                    <dl class="divide-y divide-slate-100">
                        @forelse(($selectedLog->metadata ?? []) as $key => $value)
                            <div class="grid gap-1 px-4 py-3 sm:grid-cols-[160px_1fr] items-baseline">
                                <dt class="text-xs font-bold uppercase tracking-wider text-slate-400">{{ str_replace('_',' ',$key) }}</dt>
                                <dd class="break-words font-mono text-xs text-slate-700 bg-white p-2 rounded-lg border border-slate-200/50">
                                    {{ is_array($value) ? json_encode($value, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) : $value }}
                                </dd>
                            </div>
                        @empty
                            <div class="p-5 text-sm text-slate-500 text-center">No extra metadata was recorded.</div>
                        @endforelse
                    </dl>
                </div>

                @if($selectedLog->subject_type)
                    <div class="mt-4 rounded-xl bg-amber-50/60 p-3 text-xs text-amber-900 border border-amber-200/60 font-medium">
                        Related record: <strong class="font-bold">{{ $selectedLog->subject_type }} #{{ $selectedLog->subject_id }}</strong>
                    </div>
                @endif

                <div class="mt-6 flex justify-end">
                    <button wire:click="closeDetails" class="rounded-xl bg-slate-900 px-5 py-2.5 text-xs font-bold text-white shadow-md hover:bg-slate-800 transition">
                        Close
                    </button>
                </div>
            </div>
        </div>
    @endif

</div>