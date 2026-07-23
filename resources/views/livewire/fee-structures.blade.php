<div class="space-y-6">
    <!-- Header Block with Dark Gradient & Ambient Glow -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
            <div class="max-w-2xl">
                <div class="inline-flex items-center gap-2 px-2.5 py-1 rounded-full bg-amber-400/10 border border-amber-400/20 text-amber-300 text-xs font-semibold mb-3">
                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Financial Strategy &amp; Mapping</span>
                </div>
                <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-300">Fee Structure</h1>
                <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                    Set fee amounts per class and student category for the current term. Students assigned to matching criteria are automatically mapped to their respective fee amounts.
                </p>
            </div>

            <!-- Term Context Indicator Badge -->
            @if($term)
                <div class="inline-flex items-center gap-3 bg-slate-800/90 border border-slate-700/80 rounded-xl p-3.5 text-xs text-slate-300 shrink-0 backdrop-blur-sm shadow-sm">
                    <div class="relative flex h-2.5 w-2.5">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-amber-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-amber-500"></span>
                    </div>
                    <div>
                        <div class="text-[10px] font-bold uppercase tracking-wider text-slate-400">Target Term Context</div>
                        <div class="font-bold text-amber-300 text-sm mt-0.5">{{ $term->name }}, {{ $term->year }}</div>
                    </div>
                </div>
            @endif
        </div>

        <!-- Decorative Ambient Glow -->
        <div class="absolute -right-10 -bottom-10 w-64 h-64 bg-amber-500/10 rounded-full blur-3xl pointer-events-none"></div>
    </div>

    <!-- Session Feedback Alerts -->
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
            <button type="button" onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-800 transition p-1 rounded-lg">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center justify-between gap-3 bg-rose-50/80 border border-rose-200/60 text-rose-900 text-sm rounded-xl p-4 shadow-sm backdrop-blur-sm transition-all">
            <div class="flex items-center gap-3">
                <div class="p-1 bg-rose-100 rounded-lg shrink-0">
                    <svg class="w-5 h-5 text-rose-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <span class="font-medium text-xs sm:text-sm">{{ session('error') }}</span>
            </div>
            <button type="button" onclick="this.parentElement.remove()" class="text-rose-400 hover:text-rose-700 transition p-1 rounded-lg">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    @endif

    <!-- Prerequisite Warning Alerts -->
    @if(!$term)
        <div class="flex items-center gap-3 bg-amber-50/90 border border-amber-200/80 text-amber-900 text-xs sm:text-sm rounded-xl p-4 shadow-sm">
            <div class="p-1.5 bg-amber-100 rounded-lg shrink-0">
                <svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="font-medium">No active term found for your school — fee structures are tracked per term, so an active term is required before continuing.</span>
        </div>
    @elseif($classes->isEmpty())
        <div class="flex items-center gap-3 bg-amber-50/90 border border-amber-200/80 text-amber-900 text-xs sm:text-sm rounded-xl p-4 shadow-sm">
            <div class="p-1.5 bg-amber-100 rounded-lg shrink-0">
                <svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="font-medium">You don't have any classes created yet — add at least one class before setting up fees.</span>
        </div>
    @elseif($categories->isEmpty())
        <div class="flex items-center gap-3 bg-amber-50/90 border border-amber-200/80 text-amber-900 text-xs sm:text-sm rounded-xl p-4 shadow-sm">
            <div class="p-1.5 bg-amber-100 rounded-lg shrink-0">
                <svg class="w-5 h-5 text-amber-700" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <span class="font-medium">You don't have any student categories yet — <a href="{{ route('student-categories.index') }}" wire:navigate class="underline font-bold text-amber-950 hover:text-black">create one first</a>.</span>
        </div>
    @endif

    <!-- Main Layout Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Form Card: Add Fee Structure -->
        <div class="bg-slate-900 rounded-2xl p-6 text-white shadow-sm relative overflow-hidden flex flex-col justify-between">
            <div class="relative z-10">
                <div class="flex items-center gap-2 mb-1">
                    <span class="w-2 h-2 rounded-full bg-amber-400"></span>
                    <h2 class="font-bold text-base tracking-wide text-white">Add Fee Mapping</h2>
                </div>
                <p class="text-xs text-slate-400 mb-6">Bind fee amounts to structural classes and categories.</p>

                <form wire:submit="add" class="space-y-4">
                    <!-- Class Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Class Block</label>
                        <select wire:model="school_class_id" 
                            class="w-full text-xs font-medium bg-slate-800/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-sm">
                            <option value="" class="bg-slate-900 text-slate-400">Select a class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}" class="bg-slate-900 text-white">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id') 
                            <span class="text-rose-400 font-medium text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $message }}
                            </span> 
                        @enderror
                    </div>

                    <!-- Category Selection -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Student Category</label>
                        <select wire:model="student_category_id" 
                            class="w-full text-xs font-medium bg-slate-800/80 border border-slate-700 rounded-xl px-3.5 py-2.5 text-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition shadow-sm">
                            <option value="" class="bg-slate-900 text-slate-400">Select a category</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" class="bg-slate-900 text-white">{{ $category->name }}</option>
                            @endforeach
                        </select>
                        @error('student_category_id') 
                            <span class="text-rose-400 font-medium text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $message }}
                            </span> 
                        @enderror
                    </div>

                    <!-- Amount Input -->
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-1.5">Amount (UGX)</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400 font-bold text-xs font-mono">
                                UGX
                            </div>
                            <input type="number" step="0.01" min="0" wire:model="amount" placeholder="e.g., 350000"
                                class="w-full pl-12 pr-3.5 py-2.5 text-xs bg-slate-800/80 border border-slate-700 rounded-xl focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-transparent transition font-mono font-medium text-white placeholder:text-slate-500 shadow-sm">
                        </div>
                        @error('amount') 
                            <span class="text-rose-400 font-medium text-xs mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                {{ $message }}
                            </span> 
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" 
                        wire:loading.attr="disabled" 
                        wire:target="add"
                        class="w-full inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-slate-950 font-bold text-xs px-4 py-2.5 rounded-xl transition shadow-sm hover:shadow focus:outline-none disabled:opacity-50 cursor-pointer">
                        <span wire:loading wire:target="add" class="animate-spin"><x-edlink-loader size="14" /></span>
                        <svg wire:loading.remove wire:target="add" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                        </svg>
                        <span>Save Fee Mapping</span>
                    </button>
                </form>
            </div>

            <div class="relative z-10 text-[11px] text-slate-400 border-t border-slate-800/80 pt-3 mt-6 flex items-center gap-1.5">
                <svg class="w-4 h-4 text-amber-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
                <span>Mapped fees automatically update student billing balances upon enrolment.</span>
            </div>
        </div>

        <!-- List Card: Active Fee Structures -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Current Fee Structure Matrix</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Configured rates applied to the current term.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $structures->count() }} {{ Str::plural('Entry', $structures->count()) }}
                </span>
            </div>

            @if($structures->isEmpty())
                <!-- Empty State -->
                <div class="py-12 px-6 text-center">
                    <div class="flex flex-col items-center justify-center">
                        <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-xs">
                            <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-6h6" />
                            </svg>
                        </div>
                        <p class="text-sm font-semibold text-slate-700">No Fee Structure Configured</p>
                        <p class="text-xs text-slate-400 max-w-xs mt-1">Use the "Add Fee Mapping" form to set up structural rates for your active classes.</p>
                    </div>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($structures as $fs)
                        <div class="p-4 sm:p-5 flex items-center justify-between gap-4 hover:bg-slate-50/60 transition-colors">
                            <!-- Info Section -->
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-slate-100 border border-slate-200/60 flex items-center justify-center shrink-0 text-slate-700 font-bold text-xs uppercase tracking-wider">
                                    {{ strtoupper(substr($fs->schoolClass->name, 0, 2)) }}
                                </div>
                                <div>
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <span class="font-bold text-slate-900 text-xs sm:text-sm">
                                            {{ $fs->schoolClass->name }}
                                        </span>
                                        <span class="text-slate-300">•</span>
                                        <span class="text-[10px] font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded-md border border-slate-200/60 uppercase tracking-wider">
                                            {{ $fs->studentCategory->name }}
                                        </span>
                                    </div>
                                    <div class="text-xs font-bold text-slate-900 font-mono mt-1">
                                        <span class="text-slate-400 font-sans text-[10px] uppercase mr-0.5">UGX</span>{{ number_format($fs->amount) }}
                                    </div>
                                </div>
                            </div>

                            <!-- Actions & Inline Confirm -->
                            <div class="shrink-0">
                                @if($deletingId === $fs->id)
                                    <div class="flex items-center gap-2 bg-rose-50 border border-rose-200/80 p-1.5 rounded-xl">
                                        <span class="text-xs font-bold text-rose-800 pl-1">Confirm?</span>
                                        <button wire:click="delete({{ $fs->id }})" 
                                            class="text-xs font-bold bg-rose-600 hover:bg-rose-700 text-white px-2.5 py-1 rounded-lg transition shadow-xs cursor-pointer">
                                            Yes
                                        </button>
                                        <button wire:click="cancelDelete" 
                                            class="text-xs font-semibold bg-white hover:bg-slate-100 text-slate-600 border border-slate-200 px-2.5 py-1 rounded-lg transition cursor-pointer">
                                            Cancel
                                        </button>
                                    </div>
                                @else
                                    <button wire:click="confirmDelete({{ $fs->id }})" 
                                        title="Delete Fee Rule"
                                        class="p-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-colors cursor-pointer">
                                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>