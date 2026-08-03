<div class="space-y-6">    
    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
               
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300  tracking-tight">
                    School Announcements
                </h1>
                <p class="mt-1 text-sm text-slate-500 max-w-xl">
                    Notify every school user in-app, with optional background email and SMS delivery.
                </p>
            </div>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Alert Messages -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 101.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Main Content Layout -->
    <div class="grid gap-8 lg:grid-cols-[380px_1fr] items-start">
        
        <!-- Left Column: New Announcement Form -->
        <form wire:submit="send" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 pb-4 mb-2">
                <h2 class="text-lg font-extrabold text-slate-900">
                    New Announcement
                </h2>
                <span class="h-2 w-2 rounded-full bg-amber-400"></span>
            </div>

            <div class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs leading-5 text-blue-800">
                Every announcement appears in the notification area for all user accounts belonging to this school.
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Subject</label>
                <input wire:model="title" type="text" placeholder="e.g. End of Term Briefing" class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition">
                @error('title')
                    <span class="mt-1 text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-600 mb-1.5">Message</label>
                <textarea wire:model="message" rows="7" placeholder="Write your broadcast message here..." class="w-full rounded-xl border-slate-200 bg-slate-50 text-sm focus:bg-white focus:border-yellow-400 focus:ring-2 focus:ring-yellow-400 focus:outline-none transition placeholder:text-slate-400"></textarea>
                @error('message')
                    <span class="mt-1 text-xs font-semibold text-rose-600 block">{{ $message }}</span>
                @enderror
            </div>

            <div class="space-y-3 rounded-xl border border-slate-200 bg-slate-50 p-4">
                <p class="text-xs font-bold uppercase tracking-wider text-slate-600">Additional delivery</p>
                <label class="flex cursor-pointer items-start gap-3">
                    <input wire:model="sendEmail" type="checkbox" class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    <span><strong class="block text-sm text-slate-800">Send email</strong><span class="text-xs text-slate-500">Queued for every school user with an email address.</span></span>
                </label>
                @error('sendEmail')<span class="block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
                <label class="flex cursor-pointer items-start gap-3 {{ $smsReady ? '' : 'opacity-60' }}">
                    <input wire:model="sendSms" type="checkbox" @disabled(! $smsReady) class="mt-0.5 rounded border-slate-300 text-amber-500 focus:ring-amber-400">
                    <span><strong class="block text-sm text-slate-800">Send SMS</strong><span class="text-xs text-slate-500">{{ $smsReady ? 'Queued for every school user with a phone number.' : 'SMS must first be enabled and configured by the platform administrator.' }}</span></span>
                </label>
                @error('sendSms')<span class="block text-xs font-semibold text-rose-600">{{ $message }}</span>@enderror
            </div>

            <div class="pt-2">
                <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold py-3 text-xs transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2">
                    <span wire:loading.remove class="flex items-center gap-2">
                        <svg class="h-4 w-4 text-slate-950" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <span>Send School Announcement</span>
                    </span>
                    <span wire:loading class="flex items-center gap-2">
                        <svg class="animate-spin h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span>Queueing Broadcast...</span>
                    </span>
                </button>
            </div>
        </form>

        <!-- Right Column: Sent Announcements History -->
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5 flex items-center justify-between">
                <div>
                    <h2 class="text-lg font-extrabold text-slate-900">Sent History</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Archive of dispatched broadcasts and delivery metrics</p>
                </div>
            </div>

            <div class="divide-y divide-slate-100">
                @forelse ($announcements as $announcement)
                    <article class="p-6 hover:bg-slate-50/60 transition space-y-2">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h3 class="text-base font-bold text-slate-900 leading-snug">
                                    {{ $announcement->title }}
                                </h3>
                                <div class="flex flex-wrap items-center gap-2 mt-1 text-xs text-slate-500 font-medium">
                                    <span class="inline-flex items-center gap-1 rounded-md bg-slate-100 px-2 py-0.5 text-[11px] font-bold text-slate-700">
                                        <svg class="h-3 w-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        Whole school
                                    </span>
                                    <span>•</span>
                                    <span><strong class="font-bold text-slate-800">{{ $announcement->recipient_count }}</strong> recipients</span>
                                    <span>•</span>
                                    <span>{{ $announcement->sent_at?->format('d M Y · H:i') }}</span>
                                </div>
                            </div>

                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-50 border border-emerald-200 text-emerald-800 shrink-0">
                                <span class="w-1.5 h-1.5 rounded-full bg-emerald-500"></span>
                                <span>{{ ucfirst(str_replace('_', ' ', $announcement->delivery_status)) }}</span>
                            </span>
                        </div>

                        <p class="text-xs text-slate-600 leading-relaxed font-normal whitespace-pre-line pt-1">
                            {{ $announcement->message }}
                        </p>
                    </article>
                @empty
                    <div class="p-12 text-center text-slate-400 space-y-2">
                        <div class="w-12 h-12 rounded-full bg-slate-100 border border-slate-200 text-slate-400 flex items-center justify-center mx-auto">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <p class="text-sm font-extrabold text-slate-800">No Announcements Sent</p>
                        <p class="text-xs text-slate-500 max-w-xs mx-auto">When you broadcast announcements to parents or staff, their history will be listed here.</p>
                    </div>
                @endforelse
            </div>
        </section>

    </div>
</div>
