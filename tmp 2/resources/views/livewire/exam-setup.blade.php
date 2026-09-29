<div class="space-y-6">

    <!-- Top Hero Banner -->
    <header class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-xs">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                
                <h1 class="mt-3 text-2xl font-black sm:text-3xl text-amber-300 tracking-tight">Exam Setup</h1>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-300">
                    Create draft exams, scope them by class or stream, and define term subject papers with custom max scores and weightings.
                </p>
            </div>
            
            <div class="rounded-xl border border-slate-700/80 bg-slate-800/50 px-4 py-3 text-xs backdrop-blur-md shrink-0">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Total Created Exams</span>
                <b class="mt-0.5 block text-base font-black text-amber-300">
                    {{ $exams->count() }} {{ Str::plural('exam', $exams->count()) }}
                </b>
            </div>
        </div>

        <!-- Ambient Glow Effects -->
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 bottom-0 h-32 w-32 rounded-full bg-slate-700/20 blur-2xl pointer-events-none"></div>
    </header>

    <!-- Session Status Alerts -->
    @if(session('status'))
        <div class="flex items-center gap-3 rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-2xs">
            <svg class="h-4 w-4 shrink-0 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-800 shadow-2xs">
            <svg class="h-4 w-4 shrink-0 text-rose-600" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <!-- Setup Forms Grid -->
    <div class="grid gap-6 lg:grid-cols-2 items-start">

        <!-- Form 1: Create Exam -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="border-b border-slate-100 pb-3.5">
                <h2 class="text-base font-bold text-slate-900">1. Create New Exam</h2>
                <p class="mt-0.5 text-xs font-medium text-slate-400">Define the assessment title and target class scope.</p>
            </div>

            <form wire:submit="addExam" class="mt-4 space-y-4">
                <!-- Exam Name -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Exam Title *</label>
                    <input wire:model="name" type="text" placeholder="e.g. Mid-Term Examination" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition">
                    @error('name') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                </div>

                <!-- Class Select -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Class *</label>
                    <select wire:model.live="classId" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition cursor-pointer">
                        <option value="">Select Target Class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('classId') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                </div>

                <!-- Stream Select -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Stream <span class="font-normal text-slate-400">(Optional)</span></label>
                    <select wire:model="streamId" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400/20 transition cursor-pointer">
                        <option value="">All Streams</option>
                        @foreach($streams as $stream)
                            <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                        @endforeach
                    </select>
                    @error('streamId') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-[0.99] px-5 py-3 text-xs font-black text-slate-950 shadow-xs transition cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading wire:target="addExam"><x-edlink-loader :size="16" /></span>
                        <span>Create Draft Exam</span>
                    </button>
                </div>
            </form>
        </section>

        <!-- Form 2: Add Subject Paper -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs">
            <div class="border-b border-slate-100 pb-3.5">
                <h2 class="text-base font-bold text-slate-900">2. Add Subject Paper</h2>
                <p class="mt-0.5 text-xs font-medium text-slate-400">Attach a subject paper with maximum mark scoring rules.</p>
            </div>

            <form wire:submit="addPaper" class="mt-4 space-y-4">
                <!-- Exam Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Select Exam *</label>
                    <select wire:model="examId" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800/20 transition cursor-pointer">
                        <option value="">Choose an exam</option>
                        @foreach($exams as $exam)
                            <option value="{{ $exam->id }}">{{ $exam->name }} · {{ $exam->schoolClass->name }}</option>
                        @endforeach
                    </select>
                    @error('examId') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                </div>

                <!-- Subject Selection -->
                <div>
                    <label class="block text-xs font-bold text-slate-700 mb-1.5">Subject *</label>
                    <select wire:model="subjectId" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 focus:border-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800/20 transition cursor-pointer">
                        <option value="">Choose a subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}</option>
                        @endforeach
                    </select>
                    @error('subjectId') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                </div>

                <!-- Maximum Score & Weighting (Grid) -->
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Max Score *</label>
                        <input wire:model="maximumScore" type="number" min="1" placeholder="e.g. 100" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800/20 transition">
                        @error('maximumScore') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 mb-1.5">Weighting *</label>
                        <input wire:model="weighting" type="number" min="0.01" step="0.01" placeholder="e.g. 1.00" class="block w-full rounded-xl border border-slate-200 bg-slate-50/50 px-4 py-2.5 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-slate-800 focus:bg-white focus:outline-none focus:ring-2 focus:ring-slate-800/20 transition">
                        @error('weighting') <span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span> @enderror
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="pt-2">
                    <button type="submit" wire:loading.attr="disabled" class="flex w-full items-center justify-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 active:scale-[0.99] px-5 py-3 text-xs font-black text-white shadow-xs transition cursor-pointer disabled:cursor-not-allowed disabled:opacity-50">
                        <span wire:loading wire:target="addPaper"><x-edlink-loader :size="16" /></span>
                        <span>Save Subject Paper</span>
                    </button>
                </div>
            </form>
        </section>

    </div>

    <!-- Exam Overview List -->
    <section class="rounded-2xl border border-slate-200/80 bg-white shadow-xs overflow-hidden">
        <div class="border-b border-slate-100 bg-slate-900 px-6 py-4 text-white flex items-center justify-between">
            <div>
                <h2 class="text-xs font-black uppercase tracking-wider text-amber-300">Configured Exams & Subject Papers</h2>
                <p class="text-[11px] font-medium text-slate-400 mt-0.5">List of created exams with attached subject parameters.</p>
            </div>
            <span class="rounded-md bg-slate-800 px-2.5 py-1 text-[11px] font-bold text-slate-300 border border-slate-700/80">
                {{ $exams->count() }} Total
            </span>
        </div>

        <div class="divide-y divide-slate-100">
            @forelse($exams as $exam)
                <div class="p-5 transition hover:bg-slate-50/50">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <div class="flex items-center gap-2.5">
                            <h3 class="text-sm font-bold text-slate-900">{{ $exam->name }}</h3>
                            <span class="rounded-md bg-slate-100 border border-slate-200/60 px-2 py-0.5 text-[11px] font-bold text-slate-700">
                                {{ $exam->schoolClass->name }}
                            </span>
                        </div>

                        <span class="inline-flex items-center gap-1 rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wider {{ strtolower($exam->status ?? '') === 'published' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-amber-50 text-amber-800 border border-amber-200' }}">
                            <span class="h-1.5 w-1.5 rounded-full {{ strtolower($exam->status ?? '') === 'published' ? 'bg-emerald-500' : 'bg-amber-500' }}"></span>
                            {{ $exam->status ?? 'Draft' }}
                        </span>
                    </div>

                    <!-- Papers List -->
                    <div class="mt-3">
                        <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400 mb-1.5">Subject Papers</span>
                        
                        @if($exam->papers->isNotEmpty())
                            <div class="flex flex-wrap gap-2">
                                @foreach($exam->papers as $paper)
                                    <span class="inline-flex items-center gap-1.5 rounded-lg border border-slate-200/80 bg-slate-50 px-2.5 py-1 text-xs font-medium text-slate-800">
                                        <b class="text-slate-900">{{ $paper->subject->name }}</b>
                                        <span class="text-slate-400">·</span>
                                        <span class="text-slate-500">{{ $paper->maximum_score }} pts</span>
                                        <span class="text-slate-400">·</span>
                                        <span class="font-bold text-indigo-600">wt {{ $paper->weighting }}</span>
                                    </span>
                                @endforeach
                            </div>
                        @else
                            <p class="text-xs italic text-slate-400">No subject papers added yet.</p>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-xs font-medium text-slate-400">
                    No draft or published exams setup yet. Fill in the form above to get started.
                </div>
            @endforelse
        </div>
    </section>

</div>