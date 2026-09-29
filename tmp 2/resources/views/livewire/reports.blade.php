<div class="space-y-6">
    
    <!-- HEADER BANNER WITH NAVIGATION -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl print:hidden">
        <div class="relative z-10 flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            <!-- Left Header Info & Back Button -->
            <div class="flex items-start gap-4">
                
                <div>
                   
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">Reports Center</h1>
                    <p class="mt-1 text-xs sm:text-sm text-slate-500 max-w-xl">
                        Generate, filter, and export administrative, financial, and academic reports.
                    </p>
                </div>
            </div>

            <!-- Quick Report Nav Tabs -->
            <div class="flex flex-wrap items-center gap-2">
                @if(auth()->user()->hasModuleAccess('students') || auth()->user()->hasModuleAccess('exams'))
                    <a href="{{route('reports.student-term-report')}}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/80 px-3.5 py-2 text-xs font-bold text-slate-200 hover:bg-slate-700/80 hover:text-white transition backdrop-blur-sm">
                        <i class="fa fa-file-text-o text-[11px] text-amber-400"></i><span>Term Report</span>
                    </a>
                    <a href="{{route('reports.bulk-term-reports')}}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/80 px-3.5 py-2 text-xs font-bold text-slate-200 hover:bg-slate-700/80 hover:text-white transition backdrop-blur-sm">
                        <i class="fa fa-files-o text-[11px] text-amber-400"></i><span>Bulk Reports</span>
                    </a>
                @endif
                @if(in_array(auth()->user()->role, ['admin', 'superadmin'], true))
                    <a href="{{ route('settings.audit-trail') }}" wire:navigate class="inline-flex items-center gap-2 rounded-xl border border-slate-700/80 bg-slate-800/80 px-3.5 py-2 text-xs font-bold text-slate-200 hover:bg-slate-700/80 hover:text-white transition backdrop-blur-sm">
                        <i class="fa fa-history text-[11px] text-amber-400"></i><span>Audit Trail</span>
                    </a>
                @endif
                @if(auth()->user()->hasPermission('settings.manage'))
                    <a href="{{route('reports.settings')}}" wire:navigate class="inline-flex items-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 px-3.5 py-2 text-xs font-bold text-slate-950 transition shadow-md">
                        <i class="fa fa-cog text-[11px]"></i><span>Report Settings</span>
                    </a>
                @endif
            </div>
        </div>

        <!-- Ambient Glow Effect -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- FILTER CARD PANEL -->
    <div class="rounded-3xl border border-slate-200/80 bg-white p-5 sm:p-6 shadow-sm print:hidden">
        <div class="mb-4">
            <span class="text-xs font-extrabold uppercase tracking-wider text-slate-900">Filter Records</span>
            <p class="text-xs text-slate-500">Verified school records, filtered by branch, term, category, and date range. The logged-in branch is selected by default.</p>
        </div>
        
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <!-- Branch Scope Selector -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Branch</label>
                <div class="relative">
                    <select wire:model.live="schoolScope" class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                        @foreach($branchOptions as $branch)
                            <option value="{{ $branch->id }}">{{ $branch->branch_name ?: $branch->name }} · {{ $branch->school_number }}</option>
                        @endforeach
                        @if(auth()->user()->canViewGroupDashboard())
                            <option value="all">All branches (read-only reporting)</option>
                        @endif
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
            <!-- Term Selector -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Term</label>
                <div class="relative">
                    <select wire:model.live="termId" @disabled($allBranches) class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                        <option value="">{{ $allBranches ? 'Current term in each branch' : 'Current term' }}</option>
                        @foreach($terms as $item)
                            <option value="{{ $item->id }}">{{ $item->name }}, {{ $item->year }} · {{ ucfirst($item->status) }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Gender Selector -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Gender</label>
                <div class="relative">
                    <select wire:model.live="gender" class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                        <option value="">All genders</option>
                        <option value="male">Male</option>
                        <option value="female">Female</option>
                        <option value="other">Other</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Category Selector -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Student Category</label>
                <div class="relative">
                    <select wire:model.live="categoryId" class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                        <option value="">All categories</option>
                        @foreach($categories as $category)
                            <option value="{{$category->id}}">{{$category->name}}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Fee Status Selector -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">Fee Status</label>
                <div class="relative">
                    <select wire:model.live="debtorStatus" class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                        <option value="">All learners</option>
                        <option value="debtors">Debtors only</option>
                        <option value="cleared">Cleared / no balance</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>
            
            <!-- From Date -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">From Date</label>
                <input wire:model.live="dateFrom" type="date" class="w-full text-xs px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
            </div>
            
            <!-- To Date -->
            <div>
                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1">To Date</label>
                <input wire:model.live="dateTo" type="date" class="w-full text-xs px-3.5 py-2 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
            </div>

            <!-- Clear Action -->
            <div class="flex items-end lg:col-span-2">
                <button type="button" wire:click="clearFilters" class="w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-xs font-bold text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none focus:ring-4 focus:ring-slate-200 transition duration-200">
                    Clear Student Filters
                </button>
            </div>
        </div>
        <p class="mt-3 text-[11px] text-slate-400">Gender, category, and fee-status filters apply to learner-based reports. In All branches mode, each branch uses its own current term; all results and exports remain read-only.</p>
    </div>

    <!-- REPORT TYPES SELECTOR GRID -->
    <div class="grid gap-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 print:hidden">
        @foreach($reportOptions as $key => $label)
            <button wire:click="$set('report','{{ $key }}')" 
                class="rounded-xl border p-3.5 text-left text-xs font-bold transition duration-150 flex items-center justify-between {{ $report===$key ? 'border-amber-400 bg-amber-50/80 text-slate-950 shadow-sm ring-1 ring-amber-400/80' : 'border-slate-200/80 bg-white text-slate-600 hover:border-slate-300 hover:text-slate-900' }}">
                <span>{{ $label }}</span>
                @if($report===$key)
                    <i class="fa fa-check-circle text-amber-600 text-sm"></i>
                @endif
            </button>
        @endforeach
    </div>

    <!-- ACTION EXPORT BAR -->
    <div class="flex items-center justify-between gap-4 print:hidden px-1" @if($queuedExport && in_array($queuedExport->status,['queued','processing'],true)) wire:poll.2s @endif>
        <span class="text-xs text-slate-500 font-medium">Viewing: <strong class="text-slate-900 font-bold">{{ ucwords(str_replace('_',' ',$report)) }}</strong></span>
        <div class="flex items-center gap-2">
            @if($queuedExport?->status === 'completed')
                <a href="{{ route('reports.exports.download',$queuedExport) }}" data-no-navigate class="inline-flex items-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2 text-xs font-bold text-emerald-800"><i class="fa fa-download"></i>Download CSV</a>
                <button wire:click="dismissExport" class="text-xs font-bold text-slate-500">Dismiss</button>
            @elseif($queuedExport?->status === 'failed')
                <span class="text-xs font-bold text-red-600">Export failed. Please try again.</span>
                <button wire:click="dismissExport" class="text-xs font-bold text-slate-500">Dismiss</button>
            @else
                <button wire:click="queueExport" @disabled($queuedExport) class="inline-flex items-center gap-2 rounded-xl border border-emerald-300/80 bg-emerald-50 hover:bg-emerald-100/80 px-4 py-2 text-xs font-bold text-emerald-800 transition shadow-xs disabled:opacity-50">
                    <i class="fa {{ $queuedExport ? 'fa-spinner fa-spin' : 'fa-file-excel-o' }} text-emerald-600"></i>
                    <span>{{ $queuedExport ? 'Preparing export…' : 'Export Report (.csv)' }}</span>
                </button>
            @endif
        </div>
    </div>

    <!-- MAIN REPORT DISPLAY SECTION -->
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <header class="flex items-center justify-between gap-4 border-b border-slate-100 p-5 sm:p-6 bg-slate-50/50">
            <div>
                <h2 class="text-base font-extrabold text-slate-900">{{ ucwords(str_replace('_',' ',$report)) }}</h2>
                <p class="mt-0.5 text-xs text-slate-500">{{ $allBranches ? 'All authorised branches · current term per branch' : ($term ? $term->name.', '.$term->year : 'All available school records') }}</p>
            </div>
            <button onclick="window.print()" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2 text-xs font-bold text-white transition shadow-sm print:hidden">
                <i class="fa fa-print text-[11px]"></i>
                <span>Print Report</span>
            </button>
        </header>

        @if($result['note'] ?? null)
            <div class="mx-5 mt-5 rounded-xl border border-blue-100 bg-blue-50/70 p-3.5 text-xs font-medium text-blue-700 print:hidden">
                <i class="fa fa-info-circle mr-1.5"></i>{{ $result['note'] }}
            </div>
        @endif

        <div class="max-h-[32rem] overflow-auto print:max-h-none print:overflow-visible">
            <table class="w-full text-xs text-left">
                <thead class="sticky top-0 z-10 bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px] print:static">
                    <tr>
                        @foreach($result['columns'] as $column)
                            <th class="whitespace-nowrap px-6 py-3.5">{{ $column }}</th>
                        @endforeach
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                    @forelse($result['rows'] as $row)
                        <tr class="hover:bg-slate-50/80 transition duration-150">
                            @foreach($row as $cell)
                                <td class="whitespace-nowrap px-6 py-3.5">{{ $cell }}</td>
                            @endforeach
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ max(1,count($result['columns'])) }}" class="p-12 text-center text-slate-400 italic">
                                <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                No records match this report filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(($result['pagination']['last_page'] ?? 1) > 1)
            <div class="flex flex-col gap-3 border-t border-slate-100 bg-slate-50/50 px-6 py-3.5 sm:flex-row sm:items-center sm:justify-between print:hidden">
                <p class="text-xs text-slate-500">Showing {{$result['pagination']['from']}}–{{$result['pagination']['to']}} of {{$result['pagination']['total']}} results · 40 per page</p>
                <div class="flex items-center gap-2">
                    <button wire:click="previousPage" @disabled($result['pagination']['page']<=1) class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 disabled:opacity-40 transition">Previous</button>
                    <span class="px-2 text-xs font-bold text-slate-600">Page {{$result['pagination']['page']}} of {{$result['pagination']['last_page']}}</span>
                    <button wire:click="nextPage" @disabled($result['pagination']['page']>=$result['pagination']['last_page']) class="rounded-xl border border-slate-200 bg-white px-3 py-1.5 text-xs font-bold text-slate-700 hover:bg-slate-100 disabled:opacity-40 transition">Next</button>
                </div>
            </div>
        @elseif(($result['pagination']['total'] ?? 0) > 0)
            <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-3.5 text-xs text-slate-500 print:hidden">
                Showing all {{$result['pagination']['total']}} results · up to 40 per page
            </div>
        @endif

        @if($result['summaryLabel'])
            <footer class="border-t border-slate-200/80 bg-slate-900 p-4 sm:p-5 text-right text-xs font-bold text-slate-200 flex items-center justify-between sm:justify-end gap-3">
                <span class="uppercase tracking-wider text-slate-400 text-[11px]">{{ $result['summaryLabel'] }}:</span>
                <span class="font-mono text-base font-extrabold text-amber-400">
                    {{ in_array($report, ['fee_demand','student_statement','fee_collection','debtors','cash_pool','expenses','payroll'], true) ? 'UGX ' : '' }}{{ number_format($result['summaryValue'],2) }}
                </span>
            </footer>
        @endif
    </section>

</div>
