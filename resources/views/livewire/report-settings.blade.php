<div class="space-y-6">

    <!-- TOP HEADER BANNER -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Report Settings
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-slate-400 max-w-xl">
                    Configure calculation rules, pass marks, and display options for each education stage.
                </p>
            </div>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- STATUS ALERT -->
    @if(session('status'))
        <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs sm:text-sm font-semibold text-emerald-800 shadow-sm">
            <svg class="h-5 w-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- MAIN FORM CARD -->
    <form wire:submit="save" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 sm:p-8 shadow-sm">
        
        <div class="border-b border-slate-100 pb-5 mb-6">
            <h2 class="text-base font-extrabold text-slate-900">Stage Configuration</h2>
            <p class="text-xs text-slate-500 mt-0.5">Select a stage below to customize its specific calculation and visibility parameters.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">

            <!-- Education Stage -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Education Stage</label>
                <div class="relative">
                    <select wire:model.live="stage" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-bold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        @foreach($stages as $option)
                            <option value="{{$option}}">{{ str($option)->replace('_',' ')->title() }}</option>
                        @endforeach
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
                <span class="block mt-1 text-[11px] text-slate-400">School type controls which stages are available.</span>
            </div>

            <!-- Pass Mark -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Pass Mark (%)</label>
                <input wire:model="passMark" type="number" min="0" max="100" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-bold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                <span class="block mt-1 text-[11px] text-slate-400">Average required for a pass recommendation.</span>
                @error('passMark')<span class="mt-1 block text-[11px] font-semibold text-rose-600">{{ $message }}</span>@enderror
            </div>

            <!-- Best Subjects Used -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Best Subjects Used</label>
                <input wire:model="bestSubjects" type="number" min="1" max="20" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-bold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                <span class="block mt-1 text-[11px] text-slate-400">Set 20 to use all available subjects.</span>
                @error('bestSubjects')<span class="mt-1 block text-[11px] font-semibold text-rose-600">{{ $message }}</span>@enderror
            </div>

            <!-- Show Class Position -->
            <div class="sm:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70">
                <div class="border-b border-slate-200 bg-white px-5 py-4">
                    <h3 class="text-sm font-extrabold text-slate-900">Result Calculation</h3>
                    <p class="mt-0.5 text-[11px] text-slate-500">Control how examinations are combined into the final subject result for this stage.</p>
                </div>
                <div class="grid gap-4 p-5 sm:grid-cols-2">
                    <label><span class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Calculation Method</span><select wire:model.live="calculationMethod" class="w-full rounded-xl border-slate-200 bg-white text-sm font-semibold focus:border-amber-500 focus:ring-amber-500/20"><option value="single_exam">Single selected examination</option><option value="weighted">Weighted examinations</option><option value="average">Average all examinations equally</option><option value="best_exam">Use best examination</option></select></label>
                    <label><span class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-700">Missing Assessment</span><select wire:model="missingAssessmentRule" class="w-full rounded-xl border-slate-200 bg-white text-sm font-semibold focus:border-amber-500 focus:ring-amber-500/20"><option value="incomplete">Mark result incomplete</option><option value="zero">Treat missing score as zero</option><option value="redistribute">Redistribute available weights</option></select></label>
                    @if($calculationMethod === 'weighted')
                        <div class="sm:col-span-2 rounded-xl border border-slate-200 bg-white p-4">
                            <div class="flex items-center justify-between gap-3"><div><p class="text-xs font-extrabold text-slate-800">Assessment weights</p><p class="text-[11px] text-slate-400">Names must match the examination names created in Exam Setup.</p></div><button type="button" wire:click="addAssessmentWeight" class="rounded-lg bg-slate-900 px-3 py-2 text-[11px] font-bold text-white">Add assessment</button></div>
                            <div class="mt-3 space-y-2">
                                @forelse($assessmentWeights as $index => $assessment)
                                    <div class="grid grid-cols-[1fr_110px_36px] gap-2" wire:key="assessment-weight-{{$index}}">
                                        <input wire:model="assessmentWeights.{{$index}}.name" placeholder="e.g. Mid Term" class="rounded-xl border-slate-200 bg-slate-50 text-sm focus:border-amber-500 focus:ring-amber-500/20">
                                        <div class="relative"><input wire:model="assessmentWeights.{{$index}}.weight" type="number" min="0.01" max="100" step="0.01" placeholder="Weight" class="w-full rounded-xl border-slate-200 bg-slate-50 pr-7 text-sm focus:border-amber-500 focus:ring-amber-500/20"><span class="pointer-events-none absolute right-3 top-2.5 text-xs font-bold text-slate-400">%</span></div>
                                        <button type="button" wire:click="removeAssessmentWeight({{$index}})" class="rounded-xl border border-rose-200 text-rose-600 hover:bg-rose-50" aria-label="Remove assessment"><i class="fa fa-times"></i></button>
                                    </div>
                                @empty
                                    <button type="button" wire:click="addAssessmentWeight" class="w-full rounded-xl border border-dashed border-slate-300 p-4 text-xs font-bold text-slate-500">Add the first assessment and its weight</button>
                                @endforelse
                            </div>
                            <div class="mt-3 flex justify-between text-xs"><span class="text-slate-500">Weights must equal 100%.</span><b class="{{ abs(collect($assessmentWeights)->sum(fn($row)=>(float)($row['weight']??0))-100)<0.01?'text-emerald-600':'text-amber-700' }}">Total: {{number_format(collect($assessmentWeights)->sum(fn($row)=>(float)($row['weight']??0)),1)}}%</b></div>
                            @error('assessmentWeights')<p class="mt-2 text-[11px] font-semibold text-rose-600">{{$message}}</p>@enderror
                        </div>
                    @endif
                </div>
            </div>

            <!-- Show Class Position -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Show Class Position</label>
                <div class="relative">
                    <select wire:model="showPosition" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-semibold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="enabled">Show</option>
                        <option value="disabled">Hide</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Show Attendance -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Show Attendance</label>
                <div class="relative">
                    <select wire:model="showAttendance" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-semibold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="enabled">Show</option>
                        <option value="disabled">Hide</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Show Finance Summary -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Show Finance Summary</label>
                <div class="relative">
                    <select wire:model="showFees" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-semibold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="enabled">Show</option>
                        <option value="disabled">Hide</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <!-- Show Promotion Decision -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Show Promotion Decision</label>
                <div class="relative">
                    <select wire:model="showPromotion" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-semibold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                        <option value="disabled">Hide</option>
                        <option value="enabled">Show</option>
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </div>
                </div>
            </div>

            <div class="sm:col-span-2 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50/70">
                <div class="border-b border-slate-200 bg-white px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100 text-amber-700"><i class="fa fa-table-columns text-xs"></i></span>
                        <div><h3 class="text-sm font-extrabold text-slate-900">Report Table Columns</h3><p class="mt-0.5 text-[11px] text-slate-500">Choose what appears for {{ str($stage)->replace('_',' ')->title() }}. Subject is always shown.</p></div>
                    </div>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach([
                        'showMarks' => 'Marks Scored',
                        'showMaximum' => 'Maximum Marks',
                        'showPercentage' => 'Percentage',
                        'showGrade' => 'Grade',
                        'showPoints' => 'Points / Aggregates',
                        'showRemarks' => 'Remarks',
                    ] as $property => $label)
                        <label class="group rounded-xl border border-slate-200 bg-white p-3.5 text-xs font-bold text-slate-700 shadow-xs transition hover:border-slate-300 hover:shadow-sm">
                            <span class="mb-2 block">{{ $label }}</span>
                            <span class="relative block">
                                <select wire:model="{{ $property }}" class="w-full appearance-none rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 pr-9 text-xs font-semibold text-slate-800 transition hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                                    <option value="enabled">Show on report</option>
                                    <option value="disabled">Hide from report</option>
                                </select>
                                <svg class="pointer-events-none absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </span>
                        </label>
                    @endforeach
                </div>
                <p class="px-5 pb-4 text-[11px] text-slate-400">Points and the aggregate summary are hidden by default outside lower secondary, but you can enable them here.</p>
            </div>

            <!-- Next Term Date -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Next Term Starts</label>
                <input wire:model="nextTermStarts" type="date" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-bold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                <span class="block mt-1 text-[11px] text-slate-400">This reopening date will appear on report cards for the selected education stage.</span>
                @error('nextTermStarts')<span class="mt-1 block text-[11px] font-semibold text-rose-600">{{ $message }}</span>@enderror
            </div>

            <!-- Report Footer -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Report Footer Note</label>
                <textarea wire:model="footer" rows="3" placeholder="Enter custom message to display at the bottom of reports..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10 placeholder:text-slate-400"></textarea>
                <div class="mt-1 flex items-center justify-between gap-3"><span class="text-[11px] text-slate-400">Optional message printed near the bottom of every report.</span>@error('footer')<span class="text-[11px] font-semibold text-rose-600">{{ $message }}</span>@enderror</div>
            </div>

        </div>

        <!-- Submit Button -->
        <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-end">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="inline-flex min-w-48 items-center justify-center gap-2 rounded-xl bg-amber-400 px-6 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-950 transition duration-150 hover:bg-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-400/20 shadow-md disabled:cursor-wait disabled:opacity-60">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span wire:loading.remove wire:target="save">Save Stage Settings</span>
                <span wire:loading wire:target="save">Saving Settings…</span>
            </button>
        </div>

    </form>

</div>
