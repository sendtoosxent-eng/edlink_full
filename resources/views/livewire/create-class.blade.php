<div>
    <!-- Header Block -->
    <div class="mb-8">
        <h1 class="text-2xl font-bold tracking-tight text-slate-900">Classes & Streams</h1>
        <p class="text-sm text-slate-500 mt-1.5 max-w-3xl">
            Manage your school's academic tiers. Create main classes (e.g., Primary 1) and divide them into streams or sections (e.g., North, West) for granular student tracking.
        </p>
    </div>

    <!-- Feedback Alerts -->
    @if (session('status'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-100 text-emerald-800 text-sm rounded-xl px-4 py-3.5 mb-6 shadow-sm">
            <svg class="w-5 h-5 text-emerald-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
            <span class="font-medium">{{ session('status') }}</span>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 bg-rose-50 border border-rose-100 text-rose-800 text-sm rounded-xl px-4 py-3.5 mb-6 shadow-sm">
            <svg class="w-5 h-5 text-rose-600 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span class="font-medium">{{ session('error') }}</span>
        </div>
    @endif

    <!-- Split Workspace Layout -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">

        <!-- Column 1: Configuration Panels (Forms) -->
        <div class="space-y-6">
            
            <!-- Panel A: Add Class Form -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="mb-5">
                    <h2 class="font-bold text-slate-900">Add New Class</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Create a baseline academic grade level.</p>
                </div>

                <form wire:submit="addClass" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Class Name</label>
                        <input type="text" wire:model="class_name" placeholder="e.g. Primary One, Senior 4"
                            class="w-full px-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-300">
                        @error('class_name') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="addClass"
                        class="w-full inline-flex items-center justify-center gap-2 bg-yellow-500 hover:bg-yellow-400 active:bg-yellow-600 text-slate-950 font-bold px-5 py-2.5 rounded-xl transition shadow-sm shadow-yellow-500/10 focus:outline-none disabled:opacity-60">
                        <span wire:loading wire:target="addClass" class="animate-spin"><x-edlink-loader size="16" /></span>
                        <span>Create Class</span>
                    </button>
                </form>
            </div>

            <!-- Panel B: Add Stream Form -->
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
                <div class="mb-5">
                    <h2 class="font-bold text-slate-900">Add Class Stream</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Subdivide an existing class tier into groups.</p>
                </div>

                <form wire:submit="addStream" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Target Parent Class</label>
                        <select wire:model="school_class_id" class="w-full text-sm bg-white border border-slate-200 rounded-xl px-3 py-2.5 text-slate-800 font-medium focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition shadow-sm">
                            <option value="">Select a parent class</option>
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                        @error('school_class_id') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Stream Name</label>
                        <input type="text" wire:model="stream_name" placeholder="e.g. Blue, North, Stream A"
                            class="w-full px-4 py-2.5 text-sm bg-white border border-slate-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-yellow-400 focus:border-yellow-400 transition font-medium text-slate-900 placeholder:text-slate-300">
                        @error('stream_name') <span class="text-rose-600 text-xs font-semibold mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="addStream"
                        class="w-full inline-flex items-center justify-center gap-2 bg-slate-900 hover:bg-slate-800 active:bg-black text-white text-sm font-bold px-5 py-2.5 rounded-xl transition shadow-sm focus:outline-none disabled:opacity-60">
                        <span wire:loading wire:target="addStream" class="animate-spin"><x-edlink-loader size="16" /></span>
                        <span>Attach Stream</span>
                    </button>
                </form>
            </div>

        </div>

        <!-- Column 2 & 3: Current Structure Explorer -->
        <div class="lg:col-span-2 bg-white rounded-2xl border border-slate-100 shadow-sm p-6">
            <div class="mb-5">
                <h2 class="font-bold text-slate-900">Academic Hierarchy Matrix</h2>
                <p class="text-xs text-slate-400 mt-0.5">Active classes along with their attached sub-streams.</p>
            </div>

            @if($classes->isEmpty())
                <div class="flex flex-col items-center justify-center py-16 text-center border-2 border-dashed border-slate-100 rounded-xl">
                    <svg class="w-8 h-8 text-slate-300 mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                    </svg>
                    <p class="text-sm font-semibold text-slate-600">No classes registered yet</p>
                    <p class="text-xs text-slate-400 max-w-xs mt-0.5">Use the configuration forms on the side panel to begin building out your school levels.</p>
                </div>
            @else
                <div class="space-y-4">
                    @foreach($classes as $class)
                        <div class="border border-slate-100 rounded-xl p-4 bg-slate-50/20 hover:bg-slate-50/50 transition group">
                            
                            <!-- Class Heading Row -->
                            <div class="flex items-center justify-between gap-4 mb-3 pb-3 border-b border-slate-100">
                                <div class="flex items-center gap-3">
                                    <!-- Class Block Icon Frame with theme yellow ring indicator -->
                                    <div class="w-9 h-9 rounded-xl bg-yellow-400 border border-yellow-400/20 shadow-sm flex items-center justify-center shrink-0">
                                        <span class="text-slate-950 font-black text-xs">{{ strtoupper(substr($class->name, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        @if($editingClassId === $class->id)<div class="flex items-center gap-2"><input wire:model="editingClassName" class="rounded-lg border-slate-200 py-1 text-sm"><button wire:click="saveClass" class="text-xs font-bold text-emerald-600">Save</button></div>@else<h3 class="font-bold text-slate-800 text-sm group-hover:text-yellow-600 transition-colors">{{ $class->name }}</h3>@endif
                                        <p class="text-[11px] text-slate-400 font-medium">
                                            {{ $class->streams->count() }} {{ Str::plural('stream', $class->streams->count()) }} attached
                                        </p>
                                    </div>
                                </div>

                                <!-- Class Level Delete Trigger -->
                                <div>
                                    @if($editingClassId !== $class->id)<button wire:click="startEditClass({{ $class->id }})" class="mr-2 text-xs font-bold text-slate-500 hover:text-yellow-600">Edit</button>@endif
                                    @if($deletingClassId === $class->id)
                                        <div class="flex items-center gap-1.5 bg-rose-50 border border-rose-100 p-1 rounded-lg">
                                            <span class="text-[10px] font-bold text-rose-700 px-1">Delete class safely?</span>
                                            <button wire:click="deleteClass({{ $class->id }})" class="text-[11px] font-bold bg-rose-600 text-white px-2 py-0.5 rounded-md hover:bg-rose-700 transition">Delete</button>
                                            <button wire:click="cancelDelete" class="text-[11px] font-medium bg-white border border-slate-200 text-slate-600 px-2 py-0.5 rounded-md hover:bg-slate-50">Cancel</button>
                                        </div>
                                    @else
                                        <button wire:click="confirmDeleteClass({{ $class->id }})" 
                                            title="Remove Class"
                                            class="p-1.5 rounded-lg text-slate-300 hover:text-rose-600 hover:bg-rose-50 transition">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endif
                                </div>
                            </div>

                            <!-- Nested Streams Layout -->
                            <div class="flex flex-wrap items-center gap-2">
                                @forelse($class->streams as $stream)
                                    <div class="inline-flex items-center gap-1 bg-white border border-slate-200 rounded-lg px-2.5 py-1 text-xs text-slate-700 shadow-sm font-medium hover:border-slate-300 transition group/stream">
                                        @if($editingStreamId === $stream->id)<input wire:model="editingStreamName" class="w-20 border-0 p-0 text-xs"><button wire:click="saveStream" class="text-[10px] font-bold text-emerald-600">Save</button>@else<span>{{ $stream->name }}</span><button wire:click="startEditStream({{ $stream->id }})" class="text-[10px] text-slate-400 hover:text-yellow-600">Edit</button>@endif
                                        
                                        <!-- Mini Stream Delete Trigger -->
                                        @if($deletingStreamId === $stream->id)
                                            <div class="flex items-center gap-1 ml-1 pl-1 border-l border-slate-200">
                                                <button wire:click="deleteStream({{ $stream->id }})" class="text-[10px] font-bold text-rose-600 hover:underline">Confirm</button>
                                                <button wire:click="cancelDelete" class="text-[10px] font-medium text-slate-400 hover:text-slate-600">X</button>
                                            </div>
                                        @else
                                            <button wire:click="confirmDeleteStream({{ $stream->id }})" 
                                                title="Remove Stream"
                                                class="text-slate-300 hover:text-rose-600 ml-1 opacity-0 group-hover/stream:opacity-100 transition-opacity focus:opacity-100">
                                                <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                                                </svg>
                                            </button>
                                        @endif
                                    </div>
                                @empty
                                    <span class="text-xs italic text-slate-400 pl-1">No custom subdivisions or streams mapped to this tier yet.</span>
                                @endforelse
                            </div>

                        </div>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</div>
