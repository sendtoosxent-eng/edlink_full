<div class="space-y-6">
    <!-- Header Block with Gradient Background -->
    <div class="relative overflow-hidden rounded-2xl bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8 text-white shadow-sm">
        <div class="relative z-10 max-w-3xl">
            
            <h1 class="text-2xl sm:text-3xl font-extrabold tracking-tight text-amber-300">School Events</h1>
            <p class="text-sm text-slate-400 mt-2 leading-relaxed">
                Schedule academic, sports, and general school events across terms. Dispatch targeted email reminders to parents, guardians, and staff members.
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
    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">

        <!-- Form Card (4 Cols on LG) -->
        <div class="lg:col-span-5 xl:col-span-4 bg-white rounded-2xl border border-slate-200/80 shadow-sm p-5 sm:p-6 transition-all hover:border-slate-300">
            <div class="mb-5 pb-3 border-b border-slate-100">
                <h2 class="font-bold text-slate-900 text-base">{{ $editingId ? 'Edit Event Details' : 'Create New Event' }}</h2>
                <p class="text-xs text-slate-500 mt-0.5">Configure event schedule, audience, and descriptions.</p>
            </div>

            <form wire:submit="save" class="space-y-4">
                <!-- Academic Term Select -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Academic Term</label>
                    <select wire:model="termId" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm cursor-pointer">
                        @foreach($terms as $term)
                            <option value="{{ $term->id }}">{{ $term->name }}, {{ $term->year }} · {{ ucfirst($term->status) }}</option>
                        @endforeach
                    </select>
                    @error('termId')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Event Title Input -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Event Title</label>
                    <input type="text" wire:model="title" placeholder="e.g. End of Term Parent-Teacher Conference"
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400">
                    @error('title')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Date & Type Row -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Event Date</label>
                        <input type="date" wire:model="eventDate"
                            class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-800">
                        @error('eventDate')
                            <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Event Category</label>
                        <select wire:model="type" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm cursor-pointer">
                            @foreach(['general','academic','staff','parent','sports','holiday'] as $item)
                                <option value="{{ $item }}">{{ ucfirst($item) }}</option>
                            @endforeach
                        </select>
                        @error('type')
                            <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                            </span>
                        @enderror
                    </div>
                </div>

                <!-- Audience Select -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Target Audience</label>
                    <select wire:model="audience" class="w-full text-sm bg-slate-50/50 border border-slate-200 rounded-xl px-3.5 py-2.5 text-slate-800 font-medium focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all shadow-sm cursor-pointer">
                        <option value="all">Everyone with an account</option>
                        <option value="parents">Parents / Guardians</option>
                        <option value="staff">All Staff Members</option>
                        <option value="teachers">Teaching Staff Only</option>
                    </select>
                    @error('audience')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Description Textarea -->
                <div>
                    <label class="block text-xs font-semibold text-slate-600 uppercase tracking-wider mb-1.5">Description / Agenda</label>
                    <textarea wire:model="description" rows="3" placeholder="Provide additional event details or reminders..."
                        class="w-full px-3.5 py-2.5 text-sm bg-slate-50/50 border border-slate-200 rounded-xl focus:bg-white focus:outline-none focus:ring-2 focus:ring-amber-400 focus:border-amber-400 transition-all font-medium text-slate-900 placeholder:text-slate-400"></textarea>
                    @error('description')
                        <span class="text-rose-600 text-xs font-medium mt-1.5 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5 inline" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>{{ $message }}
                        </span>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" wire:loading.attr="disabled" wire:target="save"
                        class="flex-1 inline-flex items-center justify-center gap-2 bg-amber-400 hover:bg-amber-300 active:bg-amber-500 text-slate-950 font-bold text-sm px-5 py-2.5 rounded-xl transition-all shadow-sm hover:shadow focus:outline-none disabled:opacity-60 cursor-pointer">
                        <span wire:loading wire:target="save" class="animate-spin"><x-edlink-loader size="16" /></span>
                        <span>{{ $editingId ? 'Update Event' : 'Save Event' }}</span>
                    </button>

                    @if($editingId)
                        <button type="button" wire:click="cancel"
                            class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-slate-200 bg-white text-slate-700 hover:bg-slate-50 font-semibold text-sm transition-all shadow-sm cursor-pointer">
                            Cancel
                        </button>
                    @endif
                </div>
            </form>
        </div>

        <!-- Calendar Events List (8 Cols on LG) -->
        <div class="lg:col-span-7 xl:col-span-8 bg-white rounded-2xl border border-slate-200/80 shadow-sm overflow-hidden">
            <div class="p-5 sm:p-6 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-900 text-base">Term Calendar</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Chronological list of scheduled activities for the selected term.</p>
                </div>
                <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-600">
                    {{ count($events) }} {{ Str::plural('Event', count($events)) }}
                </span>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse($events as $event)
                    <article class="p-5 sm:p-6 hover:bg-slate-50/60 transition-colors group flex flex-col sm:flex-row items-start gap-4 sm:gap-5">
                        
                        <!-- Date Badge -->
                        <div class="flex sm:flex-col items-center justify-center min-w-[3.5rem] h-14 rounded-2xl bg-amber-50 border border-amber-200/80 p-2 text-center shrink-0">
                            <span class="text-lg font-black text-amber-900 leading-none">{{ $event->event_date->format('d') }}</span>
                            <span class="text-[10px] font-bold uppercase tracking-wider text-amber-700 sm:mt-1 ml-1 sm:ml-0">{{ $event->event_date->format('M') }}</span>
                        </div>

                        <!-- Content Block -->
                        <div class="flex-1 min-w-0 w-full">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2 mb-1.5">
                                <h3 class="font-bold text-slate-900 text-base group-hover:text-amber-600 transition-colors">{{ $event->title }}</h3>
                                
                                <!-- Quick Actions -->
                                <div class="flex items-center gap-1.5 shrink-0 pt-1 sm:pt-0">
                                    <!-- Email Reminder Button -->
                                    <button wire:click="sendReminder({{ $event->id }})" 
                                        wire:confirm="Send this email reminder to the selected audience?"
                                        title="Send Email Reminder"
                                        class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg border border-slate-200 bg-slate-50 hover:bg-slate-100 text-slate-700 text-xs font-medium transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5 text-slate-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                                        </svg>
                                        <span class="hidden sm:inline">Reminder</span>
                                    </button>

                                    <!-- Edit Button -->
                                    <button wire:click="edit({{ $event->id }})" 
                                        title="Edit Event"
                                        class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg border border-amber-200 bg-amber-50 text-amber-800 text-xs font-semibold hover:bg-amber-100 transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Edit</span>
                                    </button>

                                    <!-- Delete Button -->
                                    <button wire:click="delete({{ $event->id }})" 
                                        wire:confirm="Delete this event?"
                                        title="Delete Event"
                                        class="inline-flex items-center gap-1 px-2 py-1 rounded-lg border border-rose-200 bg-rose-50 text-rose-700 text-xs font-semibold hover:bg-rose-100 transition-all cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </div>
                            </div>

                            <!-- Meta Tags -->
                            <div class="flex flex-wrap items-center gap-2 mb-2">
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-100 text-slate-700 text-xs font-medium border border-slate-200/60">
                                    {{ ucfirst($event->type) }}
                                </span>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-indigo-50 text-indigo-700 text-xs font-medium border border-indigo-200/60">
                                    Target: {{ ucfirst($event->target_audience) }}
                                </span>
                                @if($event->term)
                                    <span class="text-xs text-slate-400 font-medium">
                                        · {{ $event->term->name }}
                                    </span>
                                @endif
                            </div>

                            <!-- Optional Description -->
                            @if($event->description)
                                <p class="text-xs text-slate-600 leading-relaxed bg-slate-50/80 p-3 rounded-xl border border-slate-100 mt-2">
                                    {{ $event->description }}
                                </p>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="py-12 px-6 text-center">
                        <div class="flex flex-col items-center justify-center">
                            <div class="w-12 h-12 rounded-2xl bg-slate-50 border border-slate-200 flex items-center justify-center text-slate-400 mb-3 shadow-xs">
                                <svg class="w-6 h-6 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                                </svg>
                            </div>
                            <p class="text-sm font-semibold text-slate-700">No events scheduled for this term</p>
                            <p class="text-xs text-slate-400 max-w-xs mt-1">Use the event form on the left to add upcoming academic, sports, or general events to the calendar.</p>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>

    </div>
</div>