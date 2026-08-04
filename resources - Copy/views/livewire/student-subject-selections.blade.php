<div class="space-y-6">
    <!-- Header Banner -->
    <header class="relative overflow-hidden rounded-2xl bg-slate-900 p-6 text-white shadow-md sm:p-8">
        <div class="relative z-10 max-w-3xl">
            <h1 class="mt-3 text-2xl font-black tracking-tight sm:text-3xl text-amber-300">Individual Subject Selection</h1>
            <p class="mt-2 text-sm leading-relaxed text-slate-500">
                Manage subject allocations for Senior 3 to Senior 6 learners. O-Level tracks Core and Electives, while A-Level covers Principal and Subsidiary classification.
            </p>
        </div>
        <!-- Decorative Ambient Light -->
        <div class="absolute -right-12 -top-12 h-64 w-64 rounded-full bg-indigo-500/15 blur-3xl"></div>
    </header>

    <!-- Session Feedback -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm font-semibold text-emerald-900 shadow-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    <!-- Empty State: No Term -->
    @if (! $term)
        <div class="rounded-2xl border border-dashed border-amber-300 bg-amber-50/50 p-10 text-center">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-100 text-amber-700">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
                </svg>
            </div>
            <h2 class="mt-4 text-base font-bold text-slate-900">No Active Term Found</h2>
            <p class="mt-1 text-sm text-slate-600">Please activate an academic term before configuring learner subject selections.</p>
        </div>

    <!-- Empty State: No Secondary Classes -->
    @elseif ($classes->isEmpty())
        <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center shadow-sm">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-slate-100 text-slate-500">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 003.741-.479 3 3 0 00-4.682-2.72m.94 3.198l.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0112 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 016 18.719m12 0a5.971 5.971 0 00-.941-3.197m0 0A5.995 5.995 0 0012 12.75a5.995 5.995 0 00-5.058 2.772m0 0a5.97 5.97 0 00-.94 3.197M15 6.75a3 3 0 11-6 0 3 3 0 016 0zm6 3a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0zm-13.5 0a2.25 2.25 0 11-4.5 0 2.25 2.25 0 014.5 0z" />
                </svg>
            </div>
            <h2 class="mt-4 text-base font-bold text-slate-900">No Eligible Secondary Classes</h2>
            <p class="mt-1 text-sm text-slate-500">This module applies to Senior 3, Senior 4, Senior 5, and Senior 6 classes.</p>
        </div>

    @else
        <!-- Filter Controls -->
        <section class="grid gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm md:grid-cols-2">
            <div>
                <label for="class-select" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Select Class</label>
                <div class="relative">
                    <select id="class-select" wire:model.live="classId" class="w-full rounded-xl border-slate-300 bg-slate-50/50 py-2.5 pl-3.5 pr-8 text-sm font-bold text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                        @foreach ($classes as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->name }} — {{ $item->education_stage === 'advanced_level' ? 'A-Level' : 'O-Level' }}
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>

            <div>
                <label for="student-select" class="mb-2 block text-xs font-bold uppercase tracking-wider text-slate-600">Select Learner</label>
                <div class="relative">
                    <select id="student-select" wire:model.live="studentId" class="w-full rounded-xl border-slate-300 bg-slate-50/50 py-2.5 pl-3.5 pr-8 text-sm font-bold text-slate-800 transition focus:border-indigo-500 focus:bg-white focus:ring-indigo-500">
                        @forelse ($students as $item)
                            <option value="{{ $item->id }}">
                                {{ $item->name }}{{ $item->admission_no ? ' ('.$item->admission_no.')' : '' }}{{ $configuredStudentIds->contains($item->id) ? ' ✓' : '' }}
                            </option>
                        @empty
                            <option value="">No active learners in this class</option>
                        @endforelse
                    </select>
                </div>
            </div>
        </section>

        <!-- Main Selection Form -->
        @if ($class && $student)
            <form wire:submit="save" class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                
                <!-- Student Context Card -->
                <div class="flex flex-col gap-3 border-b border-slate-200 bg-slate-50/80 px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex items-center gap-2">
                            <h2 class="text-lg font-black text-slate-900">{{ $student->name }}</h2>
                            <span class="rounded-md bg-slate-200/60 px-2 py-0.5 text-xs font-mono font-semibold text-slate-700">
                                {{ $student->admission_no ?? 'No Reg' }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-xs font-medium text-slate-500">
                            {{ $class->name }} · {{ $term->name }} {{ $term->year }} · 
                            <strong class="text-slate-700">{{ count(array_filter($selections)) }}</strong> subjects allocated
                        </p>
                    </div>

                    <span class="inline-flex w-fit items-center gap-1.5 rounded-full px-3 py-1 text-xs font-bold ring-1 ring-inset {{ count(array_filter($selections)) ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20' : 'bg-amber-50 text-amber-800 ring-amber-600/20' }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ count(array_filter($selections)) ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                        {{ count(array_filter($selections)) ? 'Selection Configured' : 'Pending Configuration' }}
                    </span>
                </div>

                <div class="p-6">
                    <!-- Stage Guideline Alert -->
                    @if ($class->education_stage === 'advanced_level')
                        <div class="mb-6 flex items-start gap-3 rounded-xl border border-indigo-100 bg-indigo-50/60 p-4 text-xs leading-relaxed text-indigo-900">
                            <svg class="h-5 w-5 shrink-0 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <div>
                                <span class="font-bold">A-Level Requirements:</span> Select exactly <strong>3 Principal subjects</strong> and at least <strong>1 Subsidiary subject</strong> for this learner.
                            </div>
                        </div>
                    @else
                        <div class="mb-6 flex items-start gap-3 rounded-xl border border-sky-100 bg-sky-50/60 p-4 text-xs leading-relaxed text-sky-900">
                            <svg class="h-5 w-5 shrink-0 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                            </svg>
                            <div>
                                <span class="font-bold">O-Level Requirements:</span> Classify each subject taken by this learner as either <strong>Core</strong> or <strong>Elective</strong>.
                            </div>
                        </div>
                    @endif

                    <!-- Validation Errors -->
                    @error('selections')
                        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-bold text-rose-700">{{ $message }}</div>
                    @enderror
                    @error('studentId')
                        <div class="mb-5 rounded-xl border border-rose-200 bg-rose-50 p-3.5 text-xs font-bold text-rose-700">{{ $message }}</div>
                    @enderror

                    <!-- Subject Cards Grid -->
                    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        @forelse ($subjects as $subject)
                            @php
                                $currentSelection = $selections[$subject->id] ?? '';
                                $isSelected = ! empty($currentSelection);
                            @endphp

                            <div class="relative flex flex-col justify-between rounded-xl border p-4 transition-all duration-150 {{ $isSelected ? 'border-amber-400 bg-amber-50/30 shadow-sm ring-1 ring-amber-400/50' : 'border-slate-200 bg-white hover:border-slate-300' }}">
                                <div>
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="font-bold text-slate-900 leading-snug">{{ $subject->name }}</h3>
                                        @if ($subject->code)
                                            <span class="rounded bg-slate-100 px-1.5 py-0.5 font-mono text-[11px] font-semibold text-slate-500">
                                                {{ $subject->code }}
                                            </span>
                                        @endif
                                    </div>
                                </div>

                                <!-- Pill Selector Action Switch -->
                                <div class="mt-4 pt-3 border-t border-slate-100">
                                    <div class="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1 text-xs">
                                        <!-- None Option -->
                                        <button type="button" 
                                                wire:click="$set('selections.{{ $subject->id }}', '')"
                                                class="rounded-md py-1.5 font-bold transition-all {{ $currentSelection === '' ? 'bg-white text-slate-700 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                                            None
                                        </button>

                                        <!-- Dynamic Options based on $types -->
                                        @foreach ($types as $value => $label)
                                            <button type="button" 
                                                    wire:click="$set('selections.{{ $subject->id }}', '{{ $value }}')"
                                                    class="rounded-md py-1.5 font-bold transition-all {{ $currentSelection === $value ? 'bg-amber-400 text-slate-950 shadow-sm' : 'text-slate-500 hover:text-slate-900' }}">
                                                {{ $label }}
                                            </button>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-span-full rounded-2xl border border-dashed border-slate-300 p-8 text-center">
                                <p class="font-bold text-slate-700">No subjects offered to {{ $class->name }} this term.</p>
                                <a href="{{ route('subjects.index') }}" wire:navigate class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-indigo-600 hover:underline">
                                    Assign subjects to class first &rarr;
                                </a>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Sticky Footer -->
                @if ($subjects->isNotEmpty())
                    <div class="sticky bottom-4 z-10 mx-4 mb-4 flex items-center justify-between gap-4 rounded-xl border border-slate-200 bg-white/95 px-5 py-3 shadow-lg backdrop-blur">
                        <p class="hidden text-xs text-slate-500 sm:block">
                            Saves replacing existing subject selection for {{ $student->name }}.
                        </p>
                        <div class="flex items-center justify-end gap-3 w-full sm:w-auto">
                            <button type="submit" 
                                    wire:loading.attr="disabled" 
                                    class="inline-flex w-full sm:w-auto items-center justify-center gap-2 rounded-xl bg-slate-900 px-6 py-2.5 text-xs font-bold text-white shadow-sm hover:bg-slate-800 focus:outline-none focus:ring-2 focus:ring-slate-900 focus:ring-offset-2 disabled:opacity-50 transition-all">
                                <span wire:loading.remove wire:target="save">Save Selection</span>
                                <span wire:loading wire:target="save" class="inline-flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin text-white" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </div>
                @endif
            </form>
        @endif
    @endif
</div>