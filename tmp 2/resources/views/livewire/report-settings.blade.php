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
            </div>

            <!-- Best Subjects Used -->
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Best Subjects Used</label>
                <input wire:model="bestSubjects" type="number" min="1" max="20" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-bold text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                <span class="block mt-1 text-[11px] text-slate-400">Set 20 to use all available subjects.</span>
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

            <!-- Report Footer -->
            <div class="sm:col-span-2">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 mb-1">Report Footer Note</label>
                <textarea wire:model="footer" rows="3" placeholder="Enter custom message to display at the bottom of reports..." class="w-full rounded-xl border border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm font-medium text-slate-800 transition duration-200 hover:border-slate-300 focus:border-amber-500 focus:bg-white focus:outline-none focus:ring-4 focus:ring-amber-500/10 placeholder:text-slate-400"></textarea>
            </div>

        </div>

        <!-- Submit Button -->
        <div class="mt-8 pt-5 border-t border-slate-100 flex items-center justify-end">
            <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 px-6 py-3 text-xs font-extrabold uppercase tracking-wider text-slate-950 transition duration-150 hover:bg-amber-300 focus:outline-none focus:ring-4 focus:ring-amber-400/20 shadow-md">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                <span>Save Stage Settings</span>
            </button>
        </div>

    </form>

</div>