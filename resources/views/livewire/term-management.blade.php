<div class="space-y-6">
    <!-- Header Block with Dark Gradient & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-semibold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    <span>Academic Lifecycle Management</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-300">Academic Terms Calendar</h1>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                    Configure structural lifecycle schedules, track historical term logs, and handle year-over-year financial rolls.
                </p>
            </div>

            <!-- Quick Information Banner -->
            <div class="inline-flex items-start gap-3 bg-amber-500/10 border border-amber-400/20 rounded-xl p-3.5 text-xs text-amber-200 max-w-xs shrink-0 backdrop-blur-sm">
                <svg class="w-4 h-4 text-amber-400 shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
                <span>Only one term can run concurrently. Rolling arrears permanently locks metrics.</span>
            </div>
        </div>

        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center justify-between gap-3 bg-emerald-50/80 border border-emerald-200/60 text-emerald-900 text-sm rounded-xl p-4 shadow-sm backdrop-blur-sm transition-all">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-emerald-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-medium text-xs sm:text-sm">{{ session('status') }}</span>
            </div>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center justify-between gap-3 bg-rose-50/80 border border-rose-200/60 text-rose-900 text-sm rounded-xl p-4 shadow-sm backdrop-blur-sm transition-all">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-rose-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-rose-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="font-medium text-xs sm:text-sm">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    <!-- Main Stack Layout -->
    <div class="space-y-6">

        <!-- Top Row Segment: Active Hub & Entry Panel Split -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
            
            <!-- Left Panel: Modern Entry Form -->
            <div class="bg-slate-900 rounded-2xl p-6 text-white flex flex-col justify-between shadow-sm relative overflow-hidden">
                <div class="relative z-10">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                        <h2 class="font-bold text-base tracking-wide text-white">New Term Strategy</h2>
                    </div>
                    <p class="text-xs text-slate-400">Initialize your next structural block cycle.</p>
                    
                    <form wire:submit="add" class="space-y-4 mt-6">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Term Block Identity</label>
                            <input type="text" wire:model="name" placeholder="e.g., First Term"
                                class="w-full px-3.5 py-2.5 text-xs bg-slate-800/80 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition font-medium text-white placeholder:text-slate-500 shadow-sm">
                            @error('name') 
                                <span class="text-rose-400 text-xs font-medium mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                                </span> 
                            @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Target Calendar Year</label>
                            <input type="number" wire:model="year" placeholder="2026"
                                class="w-full px-3.5 py-2.5 text-xs bg-slate-800/80 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition font-mono font-medium text-white placeholder:text-slate-500 shadow-sm">
                            @error('year') 
                                <span class="text-rose-400 text-xs font-medium mt-1.5 flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                                </span> 
                            @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="add"
                            class="w-full inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-sm hover:shadow focus:outline-none disabled:opacity-50 cursor-pointer">
                            <span wire:loading wire:target="add" class="animate-spin"><x-edlink-loader size="14" /></span>
                            <svg wire:loading.remove wire:target="add" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                            </svg>
                            <span>Append to Timeline</span>
                        </button>
                    </form>
                </div>

                <div class="relative z-10 text-[11px] text-slate-400 border-t border-slate-800/80 pt-3 mt-6 flex items-center gap-1.5">
                    <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Pending terms auto-queue if a timeline slot is already occupied.</span>
                </div>
            </div>

            <!-- Right Panel: Currently Active Term Big Widget Banner -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
                @php $currentTerm = $terms->firstWhere('is_current', true); @endphp
                
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="relative flex h-2.5 w-2.5">
                                <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-emerald-500"></span>
                            </div>
                            <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Currently Open Management Hub</span>
                        </div>
                        <span class="text-[10px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/60 px-2.5 py-1 rounded-md tracking-wider">LIVE INTERFACE</span>
                    </div>

                    @if($currentTerm)
                        <div class="py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ $currentTerm->name }}</h2>
                                <p class="text-xs font-medium text-slate-500 mt-1">Calendar Allocation Block Year: <span class="font-mono font-bold text-slate-800">{{ $currentTerm->year }}</span></p>
                            </div>
                            
                            @if($closingTermId !== $currentTerm->id)
                                <button wire:click="confirmClose({{ $currentTerm->id }})" 
                                    class="shrink-0 inline-flex items-center gap-2 bg-slate-900 hover:bg-slate-800 text-white font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-sm hover:shadow active:bg-black cursor-pointer">
                                    <svg class="w-4 h-4 text-amber-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    <span>Execute Close Actions</span>
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="py-10 flex flex-col items-center justify-center text-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-xs">
                                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-bold text-slate-800">No Term Block Currently Running</p>
                            <p class="text-xs text-slate-400 mt-0.5 max-w-sm">Use the operational table ledger below to jumpstart or initialize a pending timeline segment.</p>
                        </div>
                    @endif
                </div>

                <!-- Dynamic Dropdown Confirmation Context Zone inside Dashboard Widget -->
                @if($currentTerm && $closingTermId === $currentTerm->id)
                    <div class="bg-rose-50/80 border border-rose-200/80 p-4 rounded-xl animate-fade-in transition-all">
                        <p class="text-xs font-bold text-rose-950 flex items-center gap-1.5">
                            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                            </svg>
                            <span>Select a structural wrap-up pattern for <span class="underline">{{ $currentTerm->name }}</span>:</span>
                        </p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <button wire:click="closeWithRoll({{ $currentTerm->id }})" 
                                wire:confirm="This permanently locks this term — no further edits to fees or records. Continue?"
                                class="text-xs font-bold bg-rose-600 hover:bg-rose-700 active:bg-rose-800 text-white px-3.5 py-2 rounded-lg transition shadow-sm cursor-pointer">
                                Close &amp; Roll Balances (Locked Matrix)
                            </button>
                            <button wire:click="closeWithoutRoll({{ $currentTerm->id }})"
                                class="text-xs font-bold bg-slate-900 hover:bg-slate-800 text-white px-3.5 py-2 rounded-lg transition shadow-sm cursor-pointer">
                                Standard Term Termination (Stays Open)
                            </button>
                            <button wire:click="cancelClose" class="text-xs font-semibold bg-white border border-slate-200 text-slate-600 px-3 py-2 rounded-lg hover:bg-slate-50 transition cursor-pointer">
                                Abort
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bottom Segment: Tabular Ledger Matrix -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Academic Timeline Registries</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Historical tracking overview of terms logs and lifecycle statuses.</p>
                </div>
                @if($terms->isNotEmpty())
                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                        {{ $terms->count() }} {{ Str::plural('Term', $terms->count()) }}
                    </span>
                @endif
            </div>

            @if($terms->isEmpty())
                <div class="py-12 px-6 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-xs">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No Timeline Registries Found</p>
                        <p class="text-xs text-slate-400 max-w-xs mt-1">Use the "New Term Strategy" form above to initialize your first academic term.</p>
                    </div>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 border-b border-slate-200/80 text-slate-600 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-5 py-3.5">Term Name</th>
                                <th class="px-5 py-3.5">Operational Year</th>
                                <th class="px-5 py-3.5">Lifecycle Status</th>
                                <th class="px-5 py-3.5">Archival Date</th>
                                <th class="px-5 py-3.5 text-right">Operational Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                            @foreach($terms as $term)
                                <tr class="hover:bg-slate-50/60 transition-colors @if($term->is_current) bg-amber-50/20 @endif">
                                    <!-- Term Name -->
                                    <td class="px-5 py-3.5">
                                        <div class="font-bold text-slate-900 text-xs sm:text-sm">{{ $term->name }}</div>
                                    </td>

                                    <!-- Operational Year -->
                                    <td class="px-5 py-3.5">
                                        <span class="font-mono text-xs text-slate-600 bg-slate-100 px-2 py-0.5 rounded border border-slate-200/60 font-semibold">{{ $term->year }}</span>
                                    </td>

                                    <!-- Lifecycle Status -->
                                    <td class="px-5 py-3.5">
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            @if($term->is_current)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider border border-emerald-200/60">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-600 animate-pulse"></span> Active
                                                </span>
                                            @elseif($term->status === 'pending')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-sky-50 text-sky-700 border border-sky-200/60 text-[10px] font-bold uppercase tracking-wider">
                                                    Pending
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full bg-slate-100 text-slate-600 border border-slate-200/60 text-[10px] font-bold uppercase tracking-wider">
                                                    Archived
                                                </span>
                                            @endif

                                            @if($term->locked)
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-rose-50 text-rose-700 border border-rose-200/60 text-[10px] font-bold uppercase tracking-wider">
                                                    <svg class="w-3 h-3 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                                    </svg>
                                                    Immutable
                                                </span>
                                            @endif
                                        </div>
                                    </td>

                                    <!-- Archival Date -->
                                    <td class="px-5 py-3.5 text-slate-500 font-mono text-xs">
                                        {{ $term->closed_at ? $term->closed_at->format('d M Y') : '—' }}
                                    </td>

                                    <!-- Operational Actions -->
                                    <td class="px-5 py-3.5 text-right">
                                        @if(!$term->is_current && $term->status !== 'closed')
                                            <div class="inline-flex items-center gap-2 justify-end">
                                                @if($term->status === 'pending')
                                                    <button wire:click="prepareEnrolments({{ $term->id }})"
                                                        wire:confirm="Copy active learners from the most recently closed term into this pending term for review?"
                                                        class="text-xs font-semibold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 px-3 py-1.5 rounded-lg transition shadow-xs cursor-pointer">
                                                        Prepare Learners
                                                    </button>
                                                @endif
                                                <button wire:click="openTerm({{ $term->id }})" 
                                                    class="text-xs font-bold text-slate-950 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 px-3 py-1.5 rounded-lg transition shadow-xs cursor-pointer">
                                                    Activate Context
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-xs font-normal text-slate-400 italic">No actions available</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
        
    </div>
</div>