<div class="space-y-6">
    
    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Promotion Preview &amp; Confirmation
                </h1>
                <p class="mt-1 text-sm text-slate-500 max-w-2xl">
                    Configure pass mark thresholds to evaluate learner performance. Preview promotion outcomes before writing official target-term enrolments.
                </p>
            </div>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Alert Notifications -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 101.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Step 1: Evaluation Parameters Card -->
    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-400/20 text-xs font-black text-amber-900">1</span>
                    Evaluation Parameters
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Select a closed source term and an open or pending target term to begin calculation.</p>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-4 items-end pt-1">
            <!-- Closed Source Term -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Closed Source Term</label>
                <select wire:model.live="sourceTermId" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition font-medium">
                    <option value="">Select term</option>
                    @foreach($terms->where('status','closed') as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}, {{ $term->year }}</option>
                    @endforeach
                </select>
                @error('sourceTermId')
                    <span class="mt-1 text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Target Term -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Target Term</label>
                <select wire:model.live="targetTermId" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition font-medium">
                    <option value="">Select term</option>
                    @foreach($terms->whereIn('status',['pending','open']) as $term)
                        <option value="{{ $term->id }}">{{ $term->name }}, {{ $term->year }} · {{ ucfirst($term->status) }}</option>
                    @endforeach
                </select>
                @error('targetTermId')
                    <span class="mt-1 text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Average Pass Mark -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Average Pass Mark (%)</label>
                <input wire:model.live.debounce.500ms="passMark" type="number" min="0" max="100" step="0.01" placeholder="e.g. 50" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition font-medium">
                @error('passMark')
                    <span class="mt-1 text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                @enderror
            </div>

            <!-- Evaluate Action Button -->
            <div>
                <button wire:click="generatePreview" wire:loading.attr="disabled" wire:target="generatePreview" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 text-white font-bold py-2.5 text-xs transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2 disabled:opacity-50">
                    <span wire:loading.remove wire:target="generatePreview" class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
                        <span>Evaluate All Learners</span>
                    </span>
                    <span wire:loading wire:target="generatePreview" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-amber-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Calculating...</span>
                    </span>
                </button>
            </div>
        </div>
    </section>

    <!-- Step 2 & 3: Preview Data & Confirmation Section -->
    <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between border-b border-slate-100 p-6 gap-4">
            <div>
                <h2 class="text-base font-extrabold text-slate-900 flex items-center gap-2">
                    <span class="flex h-6 w-6 items-center justify-center rounded-lg bg-amber-400/20 text-xs font-black text-amber-900">2</span>
                    Automatic Preview
                </h2>
                <p class="text-xs text-slate-500 mt-0.5">Dry-run evaluation. No enrolment modifications are written during preview.</p>
            </div>

            <!-- Aggregate Metrics Badges -->
            @if($previewReady)
                <div class="flex flex-wrap items-center gap-2">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 border border-emerald-200 px-3 py-1 text-xs font-bold text-emerald-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span>
                        <span>{{ collect($preview)->where('outcome','promoted')->count() }} Promoted</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-slate-50 border border-slate-200 px-3 py-1 text-xs font-bold text-slate-700">
                        <span>{{ collect($preview)->where('outcome','continued')->count() }} Continuing</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 border border-amber-200 px-3 py-1 text-xs font-bold text-amber-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span>
                        <span>{{ collect($preview)->where('outcome','repeated')->count() }} Repeat</span>
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-blue-50 border border-blue-200 px-3 py-1 text-xs font-bold text-blue-800">
                        <span class="h-1.5 w-1.5 rounded-full bg-blue-500"></span>
                        <span>{{ collect($preview)->where('outcome','graduated')->count() }} Graduated</span>
                    </span>
                </div>
            @endif
        </div>

        <!-- Table View -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600">
                <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider border-b border-slate-100">
                    <tr>
                        <th class="py-3.5 px-6">Learner</th>
                        <th class="py-3.5 px-6">Current Class</th>
                        <th class="py-3.5 px-6 text-center">Subjects</th>
                        <th class="py-3.5 px-6 text-center">Average</th>
                        <th class="py-3.5 px-6 text-center">Check</th>
                        <th class="py-3.5 px-6">Automatic Outcome</th>
                        <th class="py-3.5 px-6">Target Placement</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 font-medium">
                    @forelse($preview as $row)
                        <tr class="hover:bg-slate-50/60 transition">
                            <td class="py-4 px-6">
                                <p class="font-bold text-slate-900">{{ $row['student_name'] }}</p>
                                <p class="text-[11px] font-mono font-semibold text-slate-400 mt-0.5">{{ $row['admission_no'] }}</p>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-800">
                                {{ $row['current_class'] }}
                            </td>
                            <td class="py-4 px-6 text-center font-mono">
                                {{ $row['subjects'] }}
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-extrabold text-slate-900 font-mono">{{ number_format($row['average'], 1) }}%</span>
                            </td>
                            <td class="py-4 px-6 text-center whitespace-nowrap">
                                @if($row['subjects'] === 0)
                                    <span class="inline-flex items-center gap-1 rounded-full bg-rose-50 border border-rose-200 px-2.5 py-0.5 text-xs font-bold text-rose-700">
                                        No marks
                                    </span>
                                @elseif($row['passed'])
                                    <span class="inline-flex items-center gap-1 rounded-full bg-emerald-50 border border-emerald-200 px-2.5 py-0.5 text-xs font-bold text-emerald-800">
                                        Passed
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 rounded-full bg-amber-50 border border-amber-200 px-2.5 py-0.5 text-xs font-bold text-amber-800">
                                        Below {{ $passMark }}%
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6 font-extrabold">
                                <span class="inline-flex items-center gap-1.5 capitalize {{ $row['outcome'] === 'promoted' ? 'text-emerald-600' : ($row['outcome'] === 'graduated' ? 'text-blue-600' : 'text-amber-600') }}">
                                    <span class="w-1.5 h-1.5 rounded-full {{ $row['outcome'] === 'promoted' ? 'bg-emerald-500' : ($row['outcome'] === 'graduated' ? 'bg-blue-500' : 'bg-amber-500') }}"></span>
                                    <span>{{ $row['outcome'] }}</span>
                                </span>
                            </td>
                            <td class="py-4 px-6 font-semibold text-slate-800">
                                {{ $row['target_class'] }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="py-12 px-6 text-center">
                                <div class="max-w-xs mx-auto text-center space-y-2">
                                    <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                                    </div>
                                    <p class="text-sm font-extrabold text-slate-800">No Evaluation Preview Ready</p>
                                    <p class="text-xs text-slate-500">Select source and target terms, specify the average pass mark, and click "Evaluate All Learners".</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Step 3: Confirmation Footer -->
        @if($previewReady && count($preview))
            <div class="flex flex-col sm:flex-row sm:items-center justify-between border-t border-slate-200/80 bg-slate-50/80 p-5 gap-4">
                <div class="flex items-start gap-3">
                    <span class="flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-amber-400/20 text-xs font-black text-amber-900 mt-0.5">3</span>
                    <div>
                        <p class="text-xs font-extrabold text-slate-900">Confirmation &amp; Execution</p>
                        <p class="text-xs text-slate-500 mt-0.5 max-w-xl">
                            Terms 1 and 2 keep learners in the same class. After Term 3, passing learners move to the next class, final-class learners graduate, and learners below the pass mark repeat.
                        </p>
                    </div>
                </div>

                <button wire:click="commit" 
                        wire:confirm="Confirm automatic promotion for all previewed learners? This will write target-term enrolments." 
                        wire:loading.attr="disabled" 
                        wire:target="commit" 
                        class="rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold px-6 py-3 text-xs transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2 shrink-0 disabled:opacity-50">
                    <span wire:loading.remove wire:target="commit" class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                        <span>Confirm &amp; Run Promotions</span>
                    </span>
                    <span wire:loading wire:target="commit" class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Promoting Learners...</span>
                    </span>
                </button>
            </div>
        @endif
    </section>

</div>
