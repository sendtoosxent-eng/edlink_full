<div class="mx-auto max-w-5xl space-y-6">
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 text-white shadow-xl sm:p-8">
        <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-xs font-bold uppercase tracking-[0.2em] text-amber-300">Stay informed</p>
                <h1 class="mt-2 text-2xl font-extrabold sm:text-3xl">Notification Center</h1>
                <p class="mt-2 text-sm text-slate-300">School announcements and updates intended for your account.</p>
            </div>
            @if($unreadCount)
                <button wire:click="markAllRead" class="rounded-xl bg-amber-400 px-4 py-2.5 text-sm font-bold text-slate-900 transition hover:-translate-y-0.5 hover:bg-amber-300">
                    Mark all as read
                </button>
            @endif
        </div>
        <div class="pointer-events-none absolute -right-14 -top-20 h-64 w-64 rounded-full bg-amber-400/15 blur-3xl"></div>
    </header>

    <div class="flex items-center justify-between px-1">
        <p class="text-sm font-semibold text-slate-600">{{ $notifications->count() }} notifications</p>
        <span class="rounded-full bg-amber-100 px-3 py-1 text-xs font-bold text-amber-800">{{ $unreadCount }} unread</span>
    </div>

    <section class="space-y-3">
        @forelse($notifications as $notification)
            <article wire:key="notification-{{ $notification->id }}" class="rounded-2xl border bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:shadow-md {{ $notification->read_at ? 'border-slate-200' : 'border-amber-300 ring-1 ring-amber-100' }}">
                <div class="flex items-start gap-4">
                    <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl font-extrabold {{ $notification->type === 'warning' ? 'bg-amber-100 text-amber-700' : ($notification->type === 'success' ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700') }}">
                        {{ $notification->type === 'warning' ? '!' : ($notification->type === 'success' ? '✓' : 'i') }}
                    </span>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <h2 class="font-bold text-slate-900">{{ $notification->title }}</h2>
                            <time class="text-xs text-slate-400">{{ \Carbon\Carbon::parse($notification->created_at)->diffForHumans() }}</time>
                        </div>
                        <p class="mt-1 text-sm leading-6 text-slate-600">{{ $notification->message }}</p>
                        @unless($notification->read_at)
                            <button wire:click="markRead({{ $notification->id }})" class="mt-3 text-xs font-bold text-amber-700 hover:text-amber-900">Mark as read</button>
                        @endunless
                    </div>
                </div>
            </article>
        @empty
            <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center">
                <p class="font-bold text-slate-700">You are all caught up.</p>
                <p class="mt-1 text-sm text-slate-400">New school updates will appear here.</p>
            </div>
        @endforelse
    </section>
</div>
