<div class="space-y-6">
    <!-- Header Block with Background -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 max-w-3xl">
            
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-300">Subjects & Teacher Assignment</h1>
            <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                Manage your institution's course offerings. Define subject modules and pair them with active classes and assigned educators for 
                <span class="inline-flex items-center font-semibold text-amber-300 bg-amber-400/10 px-2 py-0.5 rounded border border-amber-400/20">
                    {{ $term ? $term->name . ', ' . $term->year : 'an open term' }}
                </span>.
            </p>
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
                <span class="font-medium">{{ session('status') }}</span>
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
                <span class="font-medium">{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @if($canManage)
    <!-- Split Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 items-start">
        
        <!-- Panel 1: Create Subject -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
            <div class="mb-5 pb-3 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-base">Add New Subject</h2>
                <p class="text-xs text-slate-500 mt-0.5">Define a global subject module available across grades.</p>
            </div>

            <form wire:submit="addSubject" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Subject Name</label>
                    <input type="text" wire:model="name" placeholder="e.g. Mathematics, English Literature"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                    @error('name') 
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span> 
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Subject Code <span class="text-slate-400 font-normal lowercase">(optional)</span></label>
                    <input type="text" wire:model="code" placeholder="e.g. MATH-101, ENG"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                    @error('code') 
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span> 
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="addSubject"
                    class="w-full inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-slate-950 font-bold text-sm px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                    <span wire:loading wire:target="addSubject" class="animate-spin"><x-edlink-loader size="16" /></span>
                    <span>Save Subject</span>
                </button>
            </form>
        </div>

        <!-- Panel 2: Assign Subject & Teacher -->
        <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
            <div class="mb-5 pb-3 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-base">Assign to Class & Teacher</h2>
                <p class="text-xs text-slate-500 mt-0.5">Map a subject to a specific class grade and assign an instructor.</p>
            </div>

            <form wire:submit="assign" class="space-y-4">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Target Subject</label>
                    <select wire:model="subjectId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                        <option value="">Select subject</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }} {{ $subject->code ? '('.$subject->code.')' : '' }}</option>
                        @endforeach
                    </select>
                    @error('subjectId') 
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span> 
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Target Class</label>
                    <select wire:model="classId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                        <option value="">Select class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('classId') 
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span> 
                    @enderror
                </div>

                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Assigned Teacher</label>
                    <select wire:model="teacherId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                        <option value="">Select instructor</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teacherId') 
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span> 
                    @enderror
                </div>

                <button type="submit" wire:loading.attr="disabled" wire:target="assign"
                    class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:bg-black text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                    <span wire:loading wire:target="assign" class="animate-spin"><x-edlink-loader size="16" /></span>
                    <span>Assign for Term</span>
                </button>
            </form>
        </div>

    </div>
    @endif

    <!-- Registered Subjects Explorer -->
    <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
        <div class="mb-5 pb-3 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h2 class="font-bold text-slate-900 text-base">{{ $canManage ? 'Active Subjects Directory' : 'My Subjects' }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">{{ $canManage ? 'List of all global subjects registered in the system.' : 'Subjects available through your class-teacher and teaching assignments.' }}</p>
            </div>
            <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                {{ $subjects->count() }} {{ Str::plural('Subject', $subjects->count()) }}
            </span>
        </div>

        <div class="flex flex-wrap gap-2.5">
            @forelse($subjects as $subject)
                <div class="inline-flex items-center gap-2 bg-slate-50 border border-slate-200/80 hover:border-slate-300 rounded-xl px-3 py-1.5 text-xs text-slate-800 font-medium transition-all shadow-2xs hover:bg-white group">
                    <div class="w-2 h-2 rounded-full bg-amber-400 group-hover:scale-125 transition-transform"></div>
                    <span class="font-semibold text-slate-900">{{ $subject->name }}</span>
                    @if($subject->code)
                        <span class="text-slate-400 text-[11px] font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200/60">
                            {{ $subject->code }}
                        </span>
                    @endif
                </div>
            @empty
                <div class="w-full flex flex-col items-center justify-center py-10 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                    <div class="w-10 h-10 rounded-xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 mb-2 shadow-xs">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253" />
                        </svg>
                    </div>
                    <p class="text-xs font-semibold text-slate-600">No subjects created yet</p>
                    <p class="text-[11px] text-slate-400 mt-0.5">Use the "Add New Subject" form above to create your first curriculum module.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
