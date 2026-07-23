<div class="space-y-6">
    <!-- Header Block with Dark Gradient Background & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-bold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 7h8m0 0v8m0-8l-8 8-4-4-6 6" />
                    </svg>
                    <span>Academic Lifecycle & Progression</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-black tracking-tight text-amber-300">
                    Promotion & Progression
                </h1>
                <p class="text-sm font-medium text-slate-400 mt-1.5 leading-relaxed">
                    Manage learner advancement between academic terms. Select historical source records to assign promotion outcomes and generate next-term placements.
                </p>
            </div>
        </div>
        
        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center justify-between gap-3 bg-emerald-50 border border-emerald-200/80 text-emerald-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-emerald-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-emerald-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-semibold">{{ session('status') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center justify-between gap-3 bg-rose-50 border border-rose-200/80 text-rose-900 text-sm rounded-2xl p-4 shadow-xs">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-rose-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-rose-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <span class="font-semibold">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-500 hover:text-rose-800 transition">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
        </div>
    @endif

    <!-- Cycle Parameters Card -->
    <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs p-5 sm:p-6">
        <div class="mb-5 pb-3 border-b border-slate-100">
            <h2 class="font-bold text-slate-900 text-base">Cycle Parameters</h2>
            <p class="text-xs font-medium text-slate-500 mt-0.5">Select a closed historical term and the target term receiving promoted learners.</p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-5 items-end">
            <!-- Closed Source Term -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Closed Source Term</label>
                <div class="relative">
                    <select wire:model.live="sourceTermId" wire:change="loadLearners" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all shadow-2xs appearance-none cursor-pointer">
                        <option value="">Select closed term</option>
                        @foreach($terms->where('status','closed') as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}, {{ $term->year }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                @error('sourceTermId') 
                    <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                    </span> 
                @enderror
            </div>

            <!-- Target Term -->
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-2">Target Destination Term</label>
                <div class="relative">
                    <select wire:model="targetTermId" 
                        class="w-full text-sm bg-white border border-slate-200 rounded-xl pl-3.5 pr-10 py-2.5 text-slate-800 font-semibold focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all shadow-2xs appearance-none cursor-pointer">
                        <option value="">Select target term</option>
                        @foreach($terms->whereIn('status',['pending','open']) as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}, {{ $term->year }} · {{ ucfirst($term->status) }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                        </svg>
                    </div>
                </div>
                @error('targetTermId') 
                    <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                    </span> 
                @enderror
            </div>

            <!-- Load Button -->
            <div>
                <button wire:click="loadLearners" wire:loading.attr="disabled"
                    class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:scale-98 text-white text-sm font-extrabold px-5 py-2.5 rounded-xl transition-all shadow-md hover:shadow-lg focus:outline-none disabled:opacity-60 cursor-pointer h-[42px]">
                    <span wire:loading wire:target="loadLearners" class="animate-spin"><x-edlink-loader size="16" /></span>
                    <svg wire:loading.remove wire:target="loadLearners" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span>Load Learners</span>
                </button>
            </div>
        </div>
    </section>

    <!-- Promotion Decisions Matrix Card -->
    <section class="bg-white rounded-2xl border border-slate-200/80 shadow-xs overflow-hidden">
        <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-base">Promotion Decisions Matrix</h2>
                <p class="text-xs font-medium text-slate-500 mt-0.5">Review and assign outcomes and next-term classes for active enrollments.</p>
            </div>
            @if(isset($enrolments) && $enrolments->isNotEmpty())
                <span class="inline-flex items-center rounded-full bg-slate-100 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
                    {{ $enrolments->count() }} {{ Str::plural('Learner', $enrolments->count()) }}
                </span>
            @endif
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-900 text-white font-bold uppercase tracking-wider text-[11px]">
                    <tr>
                        <th class="px-5 py-3.5">Learner</th>
                        <th class="px-5 py-3.5 text-center">Current Class</th>
                        <th class="px-5 py-3.5">Progression Outcome</th>
                        <th class="px-5 py-3.5">Next-Term Placement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($enrolments ?? [] as $e)
                        <tr class="hover:bg-slate-50/80 transition-colors">
                            <!-- Learner Info -->
                            <td class="px-5 py-3.5">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-amber-50 border border-amber-200/80 text-amber-900 font-black text-xs flex items-center justify-center shrink-0 shadow-2xs">
                                        {{ strtoupper(substr($e->student?->name ?? 'S', 0, 1)) }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="font-bold text-slate-900 truncate text-xs sm:text-sm">{{ $e->student?->name ?? '—' }}</div>
                                        <div class="text-[11px] font-mono font-semibold text-slate-400">{{ $e->student?->admission_no ?? 'No Ref' }}</div>
                                    </div>
                                </div>
                            </td>

                            <!-- Current Class Badge -->
                            <td class="px-5 py-3.5 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg bg-slate-100 text-slate-700 text-xs font-bold border border-slate-200">
                                    {{ $e->schoolClass?->name ?? 'Unassigned' }}
                                </span>
                            </td>

                            <!-- Progression Outcome Select -->
                            <td class="px-5 py-3.5">
                                <div class="relative">
                                    <select wire:model="outcomes.{{ $e->id }}" 
                                        class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl pl-3 pr-8 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all shadow-2xs appearance-none cursor-pointer">
                                        <option value="promoted">Promoted</option>
                                        <option value="repeated">Repeated</option>
                                        <option value="graduated">Graduated</option>
                                        <option value="withdrawn">Withdrawn</option>
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </td>

                            <!-- Target Class Select -->
                            <td class="px-5 py-3.5">
                                <div class="relative">
                                    <select wire:model="targetClasses.{{ $e->id }}" 
                                        class="w-full text-xs font-bold bg-white border border-slate-200 rounded-xl pl-3 pr-8 py-2 text-slate-800 focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition-all shadow-2xs appearance-none cursor-pointer">
                                        @foreach($classes as $class)
                                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                                        @endforeach
                                    </select>
                                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-slate-400">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                        </svg>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-12 px-6 text-center">
                                <div class="flex flex-col items-center justify-center max-w-xs mx-auto space-y-2">
                                    <div class="w-12 h-12 rounded-2xl bg-slate-100 text-slate-400 flex items-center justify-center text-xl shadow-2xs">
                                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l9-5-9-5-9 5 9 5z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0112 20.055a11.952 11.952 0 01-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm font-bold text-slate-800">No Promotion Records Loaded</p>
                                    <p class="text-xs font-medium text-slate-400">Select a closed source term above and click "Load Learners" to populate promotion decisions.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Sticky Commit Action Footer -->
        @if(isset($enrolments) && $enrolments->isNotEmpty())
            <div class="p-4 sm:p-5 bg-slate-50/80 border-t border-slate-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                <div class="flex items-center gap-2 text-xs text-slate-500 font-semibold">
                    <svg class="w-4 h-4 text-amber-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Committing decisions will record learner placements into the target term permanent ledger.</span>
                </div>

                <button wire:click="commit" 
                    wire:confirm="Are you sure you want to commit these promotion decisions? This action will generate target term enrollments."
                    wire:loading.attr="disabled"
                    wire:target="commit"
                    class="w-full sm:w-auto inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:scale-95 text-slate-950 font-extrabold text-sm px-5 py-2.5 rounded-xl transition-all shadow-md hover:shadow-lg focus:outline-none disabled:opacity-60 cursor-pointer shrink-0">
                    <span wire:loading wire:target="commit" class="animate-spin"><x-edlink-loader size="16" /></span>
                    <svg wire:loading.remove wire:target="commit" class="w-4 h-4 stroke-[3]" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Commit Promotion Decisions</span>
                </button>
            </div>
        @endif
    </section>
</div>