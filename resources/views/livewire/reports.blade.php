<div class="mx-auto w-full max-w-7xl print:max-w-none space-y-5">
    
    <!-- TOP NAVIGATION & BACK BUTTON -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 print:hidden">
        <div class="flex items-center gap-3">
            <button onclick="window.history.back()" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-yellow-400 border border-slate-200 text-slate-700 hover:bg-slate-100 hover:text-slate-900 transition shadow-2xs">
                <svg class="w-5 h-5 flex-shrink-0 transition-transform" :class="$store.ui.collapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" /></svg>
            </button>
            <div>
                <a href="javascript:history.back()" class="inline-flex items-center gap-1 text-[11px] font-bold text-yellow-600 hover:text-yellow-700 transition">
                    <span>Back to previous page</span>
                </a>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Reports Center</h1>
            </div>
        </div>

        <!-- QUICK REPORT NAV TABS -->
        <div class="flex flex-wrap items-center gap-2">
            @if(auth()->user()->hasModuleAccess('students') || auth()->user()->hasModuleAccess('exams'))
                <a href="{{route('reports.student-term-report')}}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                    <i class="fa fa-file-text-o text-[11px] text-slate-400"></i><span>Student term report</span>
                </a>
                <a href="{{route('reports.bulk-term-reports')}}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                    <i class="fa fa-files-o text-[11px] text-slate-400"></i><span>Bulk term reports</span>
                </a>
            @endif
            @if(in_array(auth()->user()->role, ['admin', 'superadmin'], true))
                <a href="{{ route('settings.audit-trail') }}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl border border-slate-200 bg-white px-3.5 py-2 text-xs font-bold text-slate-700 hover:bg-slate-50 transition shadow-2xs">
                    <i class="fa fa-history text-[11px] text-slate-400"></i><span>Open full Audit Trail</span>
                </a>
            @endif            @if(auth()->user()->hasPermission('settings.manage'))
                <a href="{{route('reports.settings')}}" wire:navigate class="inline-flex items-center gap-1.5 rounded-xl bg-yellow-400 hover:bg-yellow-300 px-3.5 py-2 text-xs font-bold text-slate-950 transition shadow-xs">
                    <i class="fa fa-cog text-[11px]"></i><span>Report settings</span>
                </a>
            @endif
        </div>
    </div>

    <!-- FILTER CARD (WITH YELLOW ACCENT RING) -->
    <div class="relative rounded-2xl border border-slate-200 bg-white p-4 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400 print:hidden">
        <div class="mb-3">
            <span class="text-xs font-bold text-slate-900">Filter Records</span>
            <p class="text-[11px] text-slate-500">Verified school records, filtered by the selected term and date range.</p>
        </div>
        
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">Term</label>
                <select wire:model.live="termId" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-semibold text-slate-800 transition">
                    <option value="">Current term</option>
                    @foreach($terms as $item)
                        <option value="{{ $item->id }}">{{ $item->name }}, {{ $item->year }} · {{ ucfirst($item->status) }}</option>
                    @endforeach
                </select>
            </div>
            <div><label class="block text-xs font-semibold text-slate-700 mb-1">Gender</label><select wire:model.live="gender" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl"><option value="">All genders</option><option value="male">Male</option><option value="female">Female</option><option value="other">Other</option></select></div>
            <div><label class="block text-xs font-semibold text-slate-700 mb-1">Student category</label><select wire:model.live="categoryId" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl"><option value="">All categories</option>@foreach($categories as $category)<option value="{{$category->id}}">{{$category->name}}</option>@endforeach</select></div>
            <div><label class="block text-xs font-semibold text-slate-700 mb-1">Fee status</label><select wire:model.live="debtorStatus" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl"><option value="">All learners</option><option value="debtors">Debtors only</option><option value="cleared">Cleared / no balance</option></select></div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">From</label>
                <input wire:model.live="dateFrom" type="date" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-medium text-slate-900 transition">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-slate-700 mb-1">To</label>
                <input wire:model.live="dateTo" type="date" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-medium text-slate-900 transition">
            </div>
            <div class="flex items-end"><button type="button" wire:click="clearFilters" class="w-full rounded-xl border border-slate-300 bg-white px-4 py-2.5 text-xs font-bold text-slate-700 hover:bg-slate-50">Clear student filters</button></div>
        </div>
        <p class="mt-3 text-[10px] text-slate-400">Gender, category, and fee-status filters apply to learner-based reports. Term and date filters apply wherever those records contain term or date information.</p>
    </div>

    <!-- REPORT TYPES SELECTOR GRID -->
    <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 print:hidden">
        @foreach($reportOptions as $key => $label)
            <button wire:click="$set('report','{{ $key }}')" 
                class="rounded-xl border p-3 text-left text-xs font-bold transition flex items-center justify-between {{ $report===$key ? 'border-yellow-400 bg-yellow-50 text-slate-950 shadow-2xs ring-1 ring-yellow-400' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900' }}">
                <span>{{ $label }}</span>
                @if($report===$key)
                    <i class="fa fa-check-circle text-yellow-600 text-xs"></i>
                @endif
            </button>
        @endforeach
    </div>

    <!-- ACTION EXPORT BAR -->
    <div class="flex items-center justify-between print:hidden">
        <span class="text-xs text-slate-500 font-medium">Viewing: <strong class="text-slate-900">{{ ucwords(str_replace('_',' ',$report)) }}</strong></span>
        <button wire:click="export" class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 hover:bg-emerald-100 px-3.5 py-2 text-xs font-bold text-emerald-800 transition shadow-2xs">
            <i class="fa fa-file-excel-o text-emerald-600"></i>
            <span>Export current report (.csv)</span>
        </button>
    </div>

    <!-- MAIN REPORT DISPLAY SECTION (WITH YELLOW ACCENT BAR) -->
    <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 p-5 bg-slate-50/50">
            <div>
                <h2 class="text-sm font-bold text-slate-900">{{ ucwords(str_replace('_',' ',$report)) }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $term ? $term->name.', '.$term->year : 'All available school records' }}</p>
            </div>
            <button onclick="window.print()" class="inline-flex items-center gap-1.5 rounded-xl bg-slate-900 hover:bg-slate-800 px-3.5 py-2 text-xs font-bold text-white transition shadow-2xs print:hidden">
                <i class="fa fa-print text-[11px]"></i>
                <span>Print report</span>
            </button>
        </header>

        @if($result['note'] ?? null)
            <div class="mx-5 mt-5 rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs font-medium text-blue-700 print:hidden">
                <i class="fa fa-info-circle mr-1"></i>{{ $result['note'] }}
            </div>
        @endif
            <div class="max-h-[32rem] overflow-auto print:max-h-none print:overflow-visible">
                <table class="w-full text-xs text-left">
                    <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[10px] print:static">
                        <tr>
                            @foreach($result['columns'] as $column)
                                <th class="whitespace-nowrap px-4 py-3.5">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                        @forelse($result['rows'] as $row)
                            <tr class="hover:bg-slate-50/80 transition">
                                @foreach($row as $cell)
                                    <td class="whitespace-nowrap px-4 py-3">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ max(1,count($result['columns'])) }}" class="p-10 text-center text-slate-400 italic">
                                    No records match this report filter.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if(($result['pagination']['last_page'] ?? 1) > 1)
                <div class="flex flex-col gap-3 border-t border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between print:hidden">
                    <p class="text-xs text-slate-500">Showing {{$result['pagination']['from']}}–{{$result['pagination']['to']}} of {{$result['pagination']['total']}} results · 40 per page</p>
                    <div class="flex items-center gap-2">
                        <button wire:click="previousPage" @disabled($result['pagination']['page']<=1) class="rounded-lg border px-3 py-1.5 text-xs font-bold disabled:opacity-40">Previous</button>
                        <span class="px-2 text-xs font-bold">Page {{$result['pagination']['page']}} of {{$result['pagination']['last_page']}}</span>
                        <button wire:click="nextPage" @disabled($result['pagination']['page']>=$result['pagination']['last_page']) class="rounded-lg border px-3 py-1.5 text-xs font-bold disabled:opacity-40">Next</button>
                    </div>
                </div>
            @elseif(($result['pagination']['total'] ?? 0) > 0)
                <div class="border-t px-4 py-3 text-xs text-slate-500 print:hidden">Showing all {{$result['pagination']['total']}} results · up to 40 per page</div>
            @endif

            @if($result['summaryLabel'])
                <footer class="border-t border-slate-200 bg-slate-50 p-4 text-right text-xs font-bold text-slate-900">
                    <span>{{ $result['summaryLabel'] }}: </span>
                    <span class="font-mono text-sm text-slate-950">
                        {{ in_array($report, ['fee_demand','student_statement','fee_collection','debtors','cash_pool','expenses','payroll'], true) ? 'UGX ' : '' }}{{ number_format($result['summaryValue'],2) }}
                    </span>
                </footer>
            @endif
    </section>

</div>