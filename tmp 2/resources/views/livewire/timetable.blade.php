<div class="space-y-6 pb-12">
    <!-- Top Gradient Hero Banner -->
    <section class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-xs">
        <div class="relative z-10 flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
            <div>
                
                <h1 class="mt-3 text-2xl font-black sm:text-3xl text-amber-300 tracking-tight">Create the School Timetable</h1>
                <p class="mt-1.5 max-w-2xl text-sm leading-relaxed text-slate-400">
                    Build class lessons, breaks, and activities for {{ $term ? $term->name.', '.$term->year : 'the current term' }}. Teacher and class conflicts are checked automatically.
                </p>
            </div>
            
            <div class="rounded-xl border border-slate-700/80 bg-slate-800/50 px-4 py-3 text-xs backdrop-blur-md shrink-0">
                <span class="block text-[10px] font-bold uppercase tracking-wider text-slate-400">Current Term</span>
                <b class="mt-0.5 block text-xs font-black text-amber-300">
                    {{ $term ? ($term->isOpen() ? 'Open for editing' : 'Closed') : 'Not configured' }}
                </b>
            </div>
        </div>

        <!-- Ambient Glow Backdrops -->
        <div class="absolute -right-20 -top-20 h-64 w-64 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute left-1/3 bottom-0 h-32 w-32 rounded-full bg-slate-700/20 blur-2xl pointer-events-none"></div>
    </section>

    <!-- Alert Notifications -->
    @if(session('status'))
        <div class="rounded-xl border border-emerald-200 bg-emerald-50 p-4 text-xs font-bold text-emerald-800 shadow-2xs">
            {{ session('status') }}
        </div>
    @endif

    @if(session('error'))
        <div class="rounded-xl border border-rose-200 bg-rose-50 p-4 text-xs font-bold text-rose-800 shadow-2xs">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid items-start gap-6 xl:grid-cols-[360px_minmax(0,1fr)]">
        <!-- Timetable Slot Form Side Panel -->
        <section class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs xl:sticky xl:top-24">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4">
                <div>
                    <h2 class="text-base font-bold text-slate-900">{{ $editingId ? 'Edit Timetable Slot' : 'Add Timetable Slot' }}</h2>
                    <p class="mt-0.5 text-xs font-medium text-slate-400">Fields marked with * are required.</p>
                </div>
                @if($editingId)
                    <button type="button" wire:click="cancelEdit" class="text-xs font-bold text-slate-500 hover:text-rose-600 transition">
                        Cancel
                    </button>
                @endif
            </div>

            @if(!$term)
                <div class="mt-4 rounded-xl border border-amber-200 bg-amber-50 p-3.5 text-xs font-medium text-amber-900">
                    Create and activate a term before adding timetable slots.
                </div>
            @endif

            <form wire:submit="saveSlot" class="mt-5 space-y-4">
                <!-- Class Selection -->
                <label class="block text-xs font-bold text-slate-700">
                    Class *
                    <select wire:model.live="classId" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                        <option value="">Select class</option>
                        @foreach($classes as $class)
                            <option value="{{ $class->id }}">{{ $class->name }}</option>
                        @endforeach
                    </select>
                    @error('classId')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Stream Selection -->
                <label class="block text-xs font-bold text-slate-700">
                    Stream <span class="font-normal text-slate-400">(optional)</span>
                    <select wire:model="streamId" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                        <option value="">All streams</option>
                        @foreach($streams as $stream)
                            <option value="{{ $stream->id }}">{{ $stream->name }}</option>
                        @endforeach
                    </select>
                    @error('streamId')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Day & Time Selectors -->
                <div class="grid grid-cols-2 gap-3">
                    <label class="col-span-2 block text-xs font-bold text-slate-700">
                        Day *
                        <select wire:model="day" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                            @foreach($days as $weekday)
                                <option value="{{ $weekday }}">{{ $weekday }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="block text-xs font-bold text-slate-700">
                        Starts *
                        <input wire:model="startsAt" type="time" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                        @error('startsAt')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                    </label>

                    <label class="block text-xs font-bold text-slate-700">
                        Ends *
                        <input wire:model="endsAt" type="time" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                        @error('endsAt')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                    </label>
                </div>

                <!-- Subject Selection -->
                <label class="block text-xs font-bold text-slate-700">
                    Subject
                    <select wire:model="subjectId" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                        <option value="">No subject / activity</option>
                        @foreach($subjects as $subject)
                            <option value="{{ $subject->id }}">{{ $subject->name }}{{ $subject->code ? ' ('.$subject->code.')' : '' }}</option>
                        @endforeach
                    </select>
                    @error('subjectId')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Teacher Selection -->
                <label class="block text-xs font-bold text-slate-700">
                    Teacher / Staff
                    <select wire:model="teacherId" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                        <option value="">Not assigned</option>
                        @foreach($teachers as $teacher)
                            <option value="{{ $teacher->id }}">{{ $teacher->name }}</option>
                        @endforeach
                    </select>
                    @error('teacherId')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Activity Label Field -->
                <label class="block text-xs font-bold text-slate-700">
                    Activity Label <span class="font-normal text-slate-400">(Break, Assembly…)</span>
                    <input wire:model="label" placeholder="Optional when a subject is selected" class="mt-1.5 w-full rounded-xl border-slate-200 bg-slate-50/50 text-sm font-medium text-slate-800 placeholder:text-slate-400 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                    @error('label')<span class="mt-1 block text-[11px] font-bold text-rose-600">{{ $message }}</span>@enderror
                </label>

                <!-- Save Slot Submit Button -->
                <button @disabled(!$term?->isOpen()) type="submit" 
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 active:scale-[0.99] px-5 py-3 text-xs font-black text-slate-950 shadow-xs transition disabled:cursor-not-allowed disabled:opacity-50">
                    <span wire:loading wire:target="saveSlot"><x-edlink-loader :size="18" /></span>
                    <span wire:loading.remove wire:target="saveSlot">{{ $editingId ? 'Update Slot' : 'Add to Timetable' }}</span>
                    <span wire:loading wire:target="saveSlot">Saving…</span>
                </button>
            </form>
        </section>

        <!-- Timetable View Area -->
        <section class="min-w-0 space-y-5">
            <!-- Filter Bar -->
            <div class="rounded-2xl border border-slate-200/80 bg-white p-5 shadow-xs">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-bold text-slate-900">Weekly Timetable</h2>
                        <p class="mt-0.5 text-xs font-medium text-slate-400">Select a class to view and manage its schedule.</p>
                    </div>
                    <label class="inline-flex items-center text-xs font-bold text-slate-700">
                        <span>Showing class</span>
                        <select wire:model.live="classId" class="ml-2 rounded-xl border-slate-200 bg-slate-50/50 text-xs font-bold text-slate-800 py-1.5 focus:border-amber-400 focus:bg-white focus:ring-2 focus:ring-amber-400/20 transition">
                            @foreach($classes as $class)
                                <option value="{{ $class->id }}">{{ $class->name }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>
            </div>

            @if(!$classId || $classes->isEmpty())
                <div class="rounded-2xl border-2 border-dashed border-slate-200 bg-white p-12 text-center">
                    <p class="font-bold text-slate-700">No classes available</p>
                    <p class="mt-1 text-xs text-slate-400">Create school classes before building a timetable.</p>
                </div>
            @else
                <!-- Schedule Grid per Day -->
                <div class="grid gap-4 md:grid-cols-2 2xl:grid-cols-3">
                    @foreach($days as $weekday)
                        @php($daySlots = $slotsByDay->get($weekday, collect()))
                        <article class="overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
                            <header class="flex items-center justify-between bg-slate-900 px-4 py-3 text-white">
                                <div>
                                    <h3 class="text-xs font-black uppercase tracking-wider text-amber-300">{{ $weekday }}</h3>
                                    <span class="text-[10px] font-medium text-slate-400">{{ $daySlots->count() }} {{ Str::plural('slot', $daySlots->count()) }}</span>
                                </div>
                                <span class="h-2.5 w-2.5 rounded-full {{ $daySlots->isEmpty() ? 'bg-slate-700' : 'bg-amber-400' }}"></span>
                            </header>

                            <div class="divide-y divide-slate-100">
                                @forelse($daySlots as $slot)
                                    <div class="group p-3.5 hover:bg-slate-50/80 transition">
                                        <div class="flex items-start gap-3">
                                            <div class="w-16 shrink-0 font-mono">
                                                <b class="block text-xs font-bold text-slate-900">{{ substr($slot->starts_at, 0, 5) }}</b>
                                                <span class="text-[10px] text-slate-400">to {{ substr($slot->ends_at, 0, 5) }}</span>
                                            </div>

                                            <div class="min-w-0 flex-1">
                                                <b class="block truncate text-xs font-bold text-slate-900">
                                                    {{ $slot->subject ?: ($slot->label ?: 'School Activity') }}
                                                </b>
                                                <p class="mt-0.5 truncate text-[10px] font-medium text-slate-500">
                                                    {{ $slot->teacher ?: 'No staff assigned' }}{{ $slot->stream ? ' · '.$slot->stream : ' · All streams' }}
                                                </p>
                                                @if($slot->label && $slot->subject)
                                                    <span class="mt-1.5 inline-flex rounded-md bg-amber-50 border border-amber-200/60 px-2 py-0.5 text-[9px] font-bold text-amber-900">
                                                        {{ $slot->label }}
                                                    </span>
                                                @endif
                                            </div>

                                            <div class="flex items-center gap-1 opacity-100 lg:opacity-0 lg:group-hover:opacity-100 transition-opacity">
                                                <button wire:click="editSlot({{ $slot->id }})" title="Edit" 
                                                        class="rounded-lg bg-slate-100 px-2 py-1 text-[10px] font-bold text-slate-700 hover:bg-amber-400 hover:text-slate-950 transition cursor-pointer">
                                                    Edit
                                                </button>
                                                <button wire:click="deleteSlot({{ $slot->id }})" wire:confirm="Remove this timetable slot?" title="Delete" 
                                                        class="rounded-lg bg-rose-50 px-2 py-1 text-[10px] font-bold text-rose-600 hover:bg-rose-600 hover:text-white transition cursor-pointer">
                                                    ✕
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @empty
                                    <div class="p-8 text-center text-xs font-medium text-slate-400">
                                        No activities scheduled.
                                    </div>
                                @endforelse
                            </div>
                        </article>
                    @endforeach
                </div>
            @endif
        </section>
    </div>
</div>