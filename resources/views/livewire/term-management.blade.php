<div>
    <!-- Header Block -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight text-slate-900">Academic Terms Calendar</h1>
            <p class="text-sm text-slate-500 mt-1">Configure structural lifecycle schedules and handle year-over-year financial rolls.</p>
        </div>
        
        <!-- Quick Information Banner -->
        <div class="inline-flex items-center gap-2.5 bg-amber-50/80 border border-amber-200/60 rounded-xl px-4 py-2.5 text-xs text-amber-800 font-medium max-w-sm">
            <svg class="w-4 h-4 text-amber-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Only one term can run concurrently. Rolling arrears permanently locks metrics.</span>
        </div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm rounded-xl px-4 py-3 mb-6 shadow-sm">
            <svg class="w-4 h-4 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium text-xs">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 bg-rose-50 border border-rose-100 text-rose-800 text-sm rounded-xl px-4 py-3 mb-6 shadow-sm">
            <svg class="w-4 h-4 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="font-medium text-xs">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Main Stack Layout -->
    <div class="space-y-6">

        <!-- Top Row Segment: Active Hub & Entry Panel Split -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-stretch">
            
            <!-- Left Panel: Modern Entry Form -->
            <div class="bg-slate-900 rounded-2xl p-6 text-white flex flex-col justify-between shadow-sm">
                <div>
                    <h2 class="font-bold text-base tracking-wide">New Term Strategy</h2>
                    <p class="text-[11px] text-slate-400 mt-0.5">Initialize your next structural block cycle.</p>
                    
                    <form wire:submit="add" class="space-y-3.5 mt-5">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Term Block Identity</label>
                            <input type="text" wire:model="name" placeholder="e.g., First Term"
                                class="w-full px-3.5 py-2 text-xs bg-slate-800 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition font-medium text-white placeholder:text-slate-500">
                            @error('name') <span class="text-rose-400 text-[11px] mt-1 block font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1">Target Calendar Year</label>
                            <input type="number" wire:model="year" placeholder="2026"
                                class="w-full px-3.5 py-2 text-xs bg-slate-800 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-transparent transition font-mono font-medium text-white placeholder:text-slate-500">
                            @error('year') <span class="text-rose-400 text-[11px] mt-1 block font-semibold">{{ $message }}</span> @enderror
                        </div>

                        <button type="submit" wire:loading.attr="disabled" wire:target="add"
                            class="w-full inline-flex items-center justify-center gap-2 bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-sm focus:outline-none disabled:opacity-50">
                            <span wire:loading wire:target="add" class="animate-spin"><x-edlink-loader size="14" /></span>
                            <span>Append to Timeline</span>
                        </button>
                    </form>
                </div>
                <div class="text-[10px] text-slate-400 border-t border-slate-800 pt-3 mt-4">
                    💡 Pending terms auto-queue if a timeline slot is already occupied.
                </div>
            </div>

            <!-- Right Panel: Currently Active Term Big Widget Banner -->
            <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-6 flex flex-col justify-between">
                @php $currentTerm = $terms->firstWhere('is_current', true); @endphp
                
                <div>
                    <div class="flex items-center justify-between pb-4 border-b border-slate-100">
                        <div class="flex items-center gap-2">
                            <div class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></div>
                            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider">Currently Open Management Hub</span>
                        </div>
                        <span class="text-[11px] font-mono font-bold bg-emerald-50 text-emerald-700 border border-emerald-200/50 px-2 py-0.5 rounded-md">LIVE INTERFACE</span>
                    </div>

                    @if($currentTerm)
                        <div class="py-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
                            <div>
                                <h2 class="text-3xl font-black text-slate-900 tracking-tight">{{ $currentTerm->name }}</h2>
                                <p class="text-sm font-semibold text-slate-500 mt-1">Calendar Allocation Block Year: <span class="font-mono text-slate-800">{{ $currentTerm->year }}</span></p>
                            </div>
                            
                            @if($closingTermId !== $currentTerm->id)
                                <button wire:click="confirmClose({{ $currentTerm->id }})" 
                                    class="shrink-0 inline-flex items-center gap-2 bg-white border border-slate-200 hover:border-slate-300 text-slate-700 font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-sm hover:bg-slate-50">
                                    <span>Execute Close Actions</span>
                                </button>
                            @endif
                        </div>
                    @else
                        <div class="py-8 flex flex-col items-center justify-center text-center">
                            <p class="text-sm font-bold text-slate-400">No Term Block Currently Running</p>
                            <p class="text-xs text-slate-400 mt-0.5 max-w-sm">Use the operational table ledger below to jump start or initialize a pending timeline segment.</p>
                        </div>
                    @endif
                </div>

                <!-- Dynamic Dropdown Confirmation Context Zone inside Dashboard Widget -->
                @if($currentTerm && $closingTermId === $currentTerm->id)
                    <div class="bg-rose-50/60 border border-rose-100 p-4 rounded-xl animate-fade-in">
                        <p class="text-xs font-bold text-rose-950 flex items-center gap-1.5">
                            ⚠️ Select a database structural wrap up pattern for <span class="underline">{{ $currentTerm->name }}</span>:
                        </p>
                        <div class="flex flex-wrap gap-2 mt-3">
                            <button wire:click="closeWithRoll({{ $currentTerm->id }})" 
                                wire:confirm="This permanently locks this term — no further edits to fees or records. Continue?"
                                class="text-[11px] font-bold bg-rose-600 text-white px-3 py-2 rounded-lg hover:bg-rose-700 transition shadow-sm shadow-rose-600/10">
                                Close &amp; Roll Balances (Locked Matrix)
                            </button>
                            <button wire:click="closeWithoutRoll({{ $currentTerm->id }})"
                                class="text-[11px] font-bold bg-slate-900 text-white px-3 py-2 rounded-lg hover:bg-slate-800 transition shadow-sm">
                                Standard Term Termination (Stays Open)
                            </button>
                            <button wire:click="cancelClose" class="text-[11px] font-medium bg-white border border-slate-200 text-slate-500 px-3 py-2 rounded-lg hover:bg-slate-100 transition">
                                Abort
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>

        <!-- Bottom Segment: Tabular Ledger Matrix -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-sm">Academic Timeline Registries</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Historical tracking overview of historical terms logs.</p>
                </div>
            </div>

            @if($terms->isEmpty())
                <div class="p-12 text-center text-sm font-semibold text-slate-400">
                    No timeline registries declared in system properties yet.
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-wider">
                                <th class="py-3 px-6">Term Name</th>
                                <th class="py-3 px-6">Operational Year</th>
                                <th class="py-3 px-6">Lifecycle Status</th>
                                <th class="py-3 px-6">Archival Date</th>
                                <th class="py-3 px-6 text-right">Operational Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-xs font-medium text-slate-700">
                            @foreach($terms as $term)
                                <tr class="hover:bg-slate-50/50 transition @if($term->is_current) bg-emerald-50/10 @endif">
                                    <td class="py-3.5 px-6 font-bold text-slate-900">{{ $term->name }}</td>
                                    <td class="py-3.5 px-6 font-mono text-slate-500">{{ $term->year }}</td>
                                    <td class="py-3.5 px-6">
                                        <div class="flex items-center gap-1.5">
                                            @if($term->is_current)
                                                <span class="px-2 py-0.5 rounded-md bg-emerald-100 text-emerald-800 text-[10px] font-bold uppercase tracking-wider">Active</span>
                                            @elseif($term->status === 'pending')
                                                <span class="px-2 py-0.5 rounded-md bg-blue-50 text-blue-700 text-[10px] font-bold uppercase tracking-wider">Pending</span>
                                            @else
                                                <span class="px-2 py-0.5 rounded-md bg-slate-100 text-slate-500 text-[10px] font-bold uppercase tracking-wider">Archived</span>
                                            @endif

                                            @if($term->locked)
                                                <span class="px-2 py-0.5 rounded-md bg-rose-50 text-rose-700 text-[10px] font-bold uppercase tracking-wider inline-flex items-center gap-1">
                                                    🔒 Immutable
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="py-3.5 px-6 text-slate-400">
                                        {{ $term->closed_at ? $term->closed_at->format('d M Y') : '—' }}
                                    </td>
                                    <td class="py-3.5 px-6 text-right">
                                        @if(!$term->is_current && $term->status !== 'closed')
                                            <div class="inline-flex items-center gap-2">
                                                @if($term->status === 'pending')
                                                    <button wire:click="prepareEnrolments({{ $term->id }})"
                                                        wire:confirm="Copy active learners from the most recently closed term into this pending term for review?"
                                                        class="text-[11px] font-bold text-slate-700 bg-white border border-slate-200 hover:bg-slate-50 px-3 py-1 rounded-lg transition shadow-sm">
                                                        Prepare Learners
                                                    </button>
                                                @endif
                                                <button wire:click="openTerm({{ $term->id }})" 
                                                    class="text-[11px] font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-300 px-3 py-1 rounded-lg transition shadow-sm">
                                                    Activate Context
                                                </button>
                                            </div>
                                        @else
                                            <span class="text-[11px] font-medium text-slate-300 italic">No alternative tasks</span>
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
