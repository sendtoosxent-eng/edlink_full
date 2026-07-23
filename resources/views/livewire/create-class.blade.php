<div class="space-y-6">
    <!-- Header Block with Background -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 max-w-3xl">
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-300">Classes & Streams</h1>
            <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                Manage your school's academic tiers. Create main classes (e.g., Primary 1) and divide them into streams or sections (e.g., North, West) for granular student tracking.
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

    <!-- Split Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Column 1: Configuration Panels (Forms) -->
        <div class="space-y-6">
            
            <!-- Panel A: Add Class Form -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
                <div class="mb-5 pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900 text-base">Add New Class</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Create a baseline academic grade level.</p>
                </div>

                <form wire:submit="addClass" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Class Name</label>
                        <input type="text" wire:model="class_name" placeholder="e.g. Primary One, Senior 4"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        @error('class_name') <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</span> @enderror
                        <label class="mt-4 block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Education Stage</label>
                        <select wire:model="education_stage" class="w-full rounded-xl border-slate-200 bg-slate-50/50 px-3.5 py-2.5 text-sm">
                            @foreach($educationStages as $stage)
                                <option value="{{ $stage }}">{{ str($stage)->replace('_', ' ')->title() }}</option>
                            @endforeach
                        </select>
                        @error('education_stage') <span class="text-rose-600 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="addClass"
                        class="w-full inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-slate-950 font-bold text-sm px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                        <span wire:loading wire:target="addClass" class="animate-spin"><x-edlink-loader size="16" /></span>
                        <span>Create Class</span>
                    </button>
                </form>
            </div>

            <!-- Panel B: Add Stream Form -->
            <div class="bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
                <div class="mb-5 pb-3 border-b border-slate-100">
                    <h2 class="font-bold text-slate-900 text-base">Add Class Stream</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Subdivide an existing class tier into groups.</p>
                </div>

                <form wire:submit="addStream" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Target Parent Class</label>
                        <select wire:model="school_class_id" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm">
                            <option value="">Select a parent class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id') <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Stream Name</label>
                        <input type="text" wire:model="stream_name" placeholder="e.g. Blue, North, Stream A"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                        @error('stream_name') <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1"><svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="addStream"
                        class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:bg-black text-white text-sm font-semibold px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                        <span wire:loading wire:target="addStream" class="animate-spin"><x-edlink-loader size="16" /></span>
                        <span>Attach Stream</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- Column 2 & 3: Current Structure Explorer -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6">
            <div class="mb-5 pb-3 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Academic Hierarchy Matrix</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Active classes along with their attached sub-streams.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ $classes->count() }} {{ Str::plural('Class', $classes->count()) }}
                </span>
            </div>

            @if($classes->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center border-2 border-dashed border-slate-200 rounded-2xl bg-slate-50/50">
                    <div class="w-12 h-12 rounded-2xl bg-white border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-sm">
                        <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                        </svg>
                    </div>
                    <p class="text-sm font-semibold text-slate-700">No classes registered yet</p>
                    <p class="text-xs text-slate-400 max-w-xs mt-1">Use the configuration forms on the side panel to begin building out your school levels.</p>
                </div>
            @else
                <div class="space-y-3.5">
                    @foreach($classes as $class)
                        <div class="border border-slate-200/80 rounded-xl p-4 bg-white hover:border-slate-300 transition-all shadow-xs group">
                            
                            <!-- Class Heading Row -->
                            <div class="flex items-center justify-between gap-4 mb-3 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-3 flex-1 min-w-0">
                                    <!-- Badge Icon -->
                                    <div class="w-10 h-10 rounded-xl bg-amber-400/90 text-slate-950 font-black text-xs flex items-center justify-center shrink-0 shadow-sm border border-amber-300">
                                        {{ strtoupper(substr($class->name, 0, 2)) }}
                                    </div>

                                    <div class="flex-1 min-w-0">
                                        @if($editingClassId === $class->id)
                                            <div class="flex items-center gap-2 max-w-sm">
                                                <input type="text" wire:model="editingClassName" 
                                                    wire:keydown.enter="saveClass" 
                                                    wire:keydown.escape="$set('editingClassId', null)"
                                                    class="px-2.5 py-1 text-sm bg-white border border-amber-400 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-400 font-medium text-slate-900 w-full"
                                                    autofocus>
                                                <button wire:click="saveClass" class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-semibold rounded-lg transition shadow-xs">Save</button>
                                                <button wire:click="$set('editingClassId', null)" class="px-2 py-1 bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-medium rounded-lg transition">Cancel</button>
                                            </div>
                                        @else
                                            <h3 class="font-bold text-slate-800 text-sm truncate group-hover:text-amber-600 transition-colors">{{ $class->name }}</h3>
                                            <p class="text-[11px] text-slate-400 font-medium mt-0.5">
                                                {{ $class->streams->count() }} {{ Str::plural('stream', $class->streams->count()) }} attached
                                            </p>
                                        @endif
                                    </div>
                                </div>

                                <!-- Class Level Actions -->
                                <div class="flex items-center gap-1 shrink-0">
                                    @if($editingClassId !== $class->id)
                                        <button wire:click="startEditClass({{ $class->id }})" 
                                            class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition"
                                            title="Edit class name">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 210.382 2 1 2 6.5l12.732-12.732z" />
                                            </svg>
                                        </button>
                                    @endif

                                    @if($deletingClassId === $class->id)
                                        <div class="flex items-center gap-1.5 bg-rose-50 border border-rose-200 p-1 rounded-lg">
                                            <span class="text-[11px] font-semibold text-rose-700 px-1">Delete?</span>
                                            <button wire:click="deleteClass({{ $class->id }})" class="text-xs font-semibold bg-rose-600 text-white px-2 py-0.5 rounded hover:bg-rose-700 transition">Yes</button>
                                            <button wire:click="cancelDelete" class="text-xs font-medium bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded hover:bg-slate-50 transition">No</button>
                                        </div>
                                    @else
                                        <button wire:click="confirmDeleteClass({{ $class->id }})" 
                                            title="Remove Class"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Nested Streams Layout -->
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                @forelse($class->streams as $stream)
                                    <div class="inline-flex items-center gap-1.5 bg-slate-50 border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-700 shadow-2xs font-medium hover:bg-white hover:border-slate-300 transition-all group/stream">
                                        @if($editingStreamId === $stream->id)
                                            <div class="flex items-center gap-1">
                                                <input type="text" wire:model="editingStreamName" 
                                                    wire:keydown.enter="saveStream"
                                                    wire:keydown.escape="$set('editingStreamId', null)"
                                                    class="w-20 px-1.5 py-0.5 border border-amber-400 rounded text-xs focus:outline-none bg-white font-medium" autofocus>
                                                <button wire:click="saveStream" class="text-emerald-600 hover:text-emerald-700 p-0.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                </button>
                                                <button wire:click="$set('editingStreamId', null)" class="text-slate-400 hover:text-slate-600 p-0.5">
                                                    <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                                </button>
                                            </div>
                                        @else
                                            <span>{{ $stream->name }}</span>
                                            
                                            <!-- Mini Stream Edit Action -->
                                            <button wire:click="startEditStream({{ $stream->id }})" class="text-slate-300 hover:text-amber-600 opacity-0 group-hover/stream:opacity-100 transition-opacity focus:opacity-100">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 210.382 2 1 2 6.5l12.732-12.732z"/></svg>
                                            </button>
                                        @endif
                                        
                                        <!-- Mini Stream Delete Trigger -->
                                        @if($deletingStreamId === $stream->id)
                                            <div class="flex items-center gap-1 ml-1 pl-1.5 border-l border-slate-200">
                                                <button wire:click="deleteStream({{ $stream->id }})" class="text-[10px] font-bold text-rose-600 hover:underline">Confirm</button>
                                                <button wire:click="cancelDelete" class="text-[10px] font-medium text-slate-400 hover:text-slate-600">×</button>
                                            </div>
                                        @else
                                            <button wire:click="confirmDeleteStream({{ $stream->id }})" 
                                                title="Remove Stream"
                                                class="text-slate-300 hover:text-rose-600 opacity-0 group-hover/stream:opacity-100 transition-opacity focus:opacity-100">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-xs italic text-slate-400">No custom subdivisions or streams mapped to this tier yet.</span>
                                @endforelse
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>