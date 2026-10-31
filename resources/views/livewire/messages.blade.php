<div wire:poll.2s.visible class="mx-auto max-w-7xl space-y-5">
    <div class="flex flex-wrap items-center justify-between gap-3">
        <div><p class="text-xs font-bold uppercase tracking-widest text-amber-600">School conversations</p><h1 class="mt-1 text-2xl font-bold text-slate-900">Messages</h1><p class="mt-1 text-sm text-slate-500">Connect with people in {{ $user->school->name }}.</p></div>
        <span class="inline-flex items-center gap-2 rounded-full border bg-white px-3 py-2 text-xs text-slate-600" wire:offline.class="hidden"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>Auto updates · every 2 seconds</span>
        <span wire:offline class="text-sm text-amber-700">You’re offline. Reconnect to send and receive messages.</span>
    </div>

    <div class="grid overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:grid-cols-[280px_minmax(0,1fr)]" style="height: min(740px, calc(100dvh - 220px)); min-height: 460px;">
        <aside class="min-h-0 flex-col border-r border-slate-100 {{ $peer ? 'hidden md:flex' : 'flex' }}">
            <div class="border-b border-slate-100 p-4">
                <h2 class="font-semibold text-slate-900">Your school</h2>
                <label for="chat-search" class="sr-only">Find someone by name</label>
                <input id="chat-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Find someone by name…" maxlength="100" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none">
                <p class="mt-2 text-xs text-slate-400">Recent chats first. Search to find more people.</p>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-2">
                @forelse($contacts as $contact)
                    <button type="button" wire:key="contact-{{ $contact->id }}" wire:click="openConversation({{ $contact->id }})" class="mb-1 flex w-full items-center gap-3 rounded-xl p-3 text-left transition hover:bg-amber-50 {{ $peerId === $contact->id ? 'bg-amber-50 ring-1 ring-inset ring-amber-200' : '' }}">
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-slate-100 font-semibold text-slate-600">{{ mb_strtoupper(mb_substr($contact->name, 0, 1)) }}</span>
                        <span class="min-w-0 flex-1"><span class="block truncate text-sm font-semibold text-slate-800">{{ $contact->name }}</span><span class="block text-xs capitalize text-slate-400">{{ str_replace('_', ' ', $contact->role) }}</span></span>
                        @if($contact->unread_count)<span class="rounded-full bg-amber-400 px-2 py-0.5 text-xs font-bold text-slate-900" aria-label="{{ $contact->unread_count }} unread messages">{{ $contact->unread_count }}</span>@endif
                    </button>
                @empty
                    <p class="p-5 text-center text-sm text-slate-400">{{ $search ? 'No people match your search.' : 'No other active accounts in your school yet.' }}</p>
                @endforelse
            </div>
        </aside>

        @if($peer)
            <section wire:key="thread-{{ $peer->id }}" class="flex min-h-0 min-w-0 flex-col" x-data="{
                nearBottom: true, observer: null,
                init() { this.$nextTick(() => {
                    this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight;
                    this.observer = new MutationObserver(() => { if (this.nearBottom) this.$nextTick(() => this.$refs.thread.scrollTop = this.$refs.thread.scrollHeight) });
                    this.observer.observe(this.$refs.thread, { childList: true, subtree: true });
                }) },
                destroy() { this.observer?.disconnect() }
            }"
                x-on:chat-sent.window="$nextTick(() => { $refs.thread.scrollTop = $refs.thread.scrollHeight; nearBottom = true })">
                <header class="flex items-center gap-3 border-b border-slate-100 px-4 py-4">
                    <button type="button" wire:click="closeConversation" class="rounded-lg border px-2 py-1 text-sm md:hidden" aria-label="Back to conversations">←</button>
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-amber-100 font-semibold text-amber-800">{{ mb_strtoupper(mb_substr($peer->name, 0, 1)) }}</span>
                    <div><h2 class="text-sm font-bold text-slate-900">{{ $peer->name }}</h2><p class="text-xs capitalize text-slate-400">{{ str_replace('_', ' ', $peer->role) }} · Private conversation</p></div>
                </header>
                <div x-ref="thread" x-on:scroll="nearBottom = $el.scrollHeight - $el.scrollTop - $el.clientHeight < 100" class="min-h-0 flex-1 space-y-4 overflow-y-auto bg-slate-50 p-4 sm:p-6" aria-label="Conversation messages" role="log" aria-live="polite" aria-relevant="additions">
                    @if($hasOlder)
                        @if($messageLimit < 500)<div class="text-center"><button type="button" wire:click="loadOlder" class="rounded-full border bg-white px-4 py-2 text-xs font-semibold text-slate-600">Load older messages</button></div>
                        @else<p class="text-center text-xs text-slate-400">Showing the latest 500 messages.</p>@endif
                    @endif
                    @forelse($messages as $message)
                        <div wire:key="message-{{ $message->id }}" class="flex {{ $message->sender_id === $user->id ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 {{ $message->sender_id === $user->id ? 'rounded-br-sm bg-[#252641] text-white' : 'rounded-bl-sm border border-slate-200 bg-white text-slate-800' }}">
                                <p class="whitespace-pre-wrap break-words text-sm" style="overflow-wrap: anywhere;">{{ $message->body }}</p>
                                <p class="mt-2 text-right text-[10px] {{ $message->sender_id === $user->id ? 'text-slate-300' : 'text-slate-400' }}"><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d M, H:i') }}</time>@if($message->sender_id === $user->id) · {{ $message->read_at ? 'Read' : 'Sent' }}@endif</p>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center text-center"><p class="font-semibold text-slate-700">Start a conversation with {{ $peer->name }}</p><p class="mt-2 text-sm text-slate-400">Send your first message below.</p></div>
                    @endforelse
                </div>
                <form wire:submit="send" class="border-t border-slate-100 p-4">
                    <label for="chat-body" class="sr-only">Your message</label>
                    <div class="flex items-end gap-3">
                        <textarea id="chat-body" wire:model="body" rows="2" maxlength="5000" placeholder="Write a message…" class="max-h-36 min-w-0 flex-1 resize-y rounded-xl border border-slate-200 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none"></textarea>
                        <button type="submit" wire:loading.attr="disabled" wire:target="send" wire:offline.attr="disabled" class="rounded-xl bg-amber-400 px-5 py-3 text-sm font-bold text-slate-900 hover:bg-amber-500 disabled:opacity-50"><span wire:loading.remove wire:target="send">Send</span><span wire:loading wire:target="send">Sending…</span></button>
                    </div>
                    @error('body')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                    <p class="mt-2 text-[11px] text-slate-400">Visible only to you and {{ $peer->name }}. Up to 5,000 characters.</p>
                </form>
            </section>
        @else
            <section class="hidden flex-col items-center justify-center bg-slate-50 p-8 text-center md:flex">
                <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-amber-100 text-amber-700"><x-heroicon-o-chat-bubble-left-right class="h-8 w-8" /></span>
                <h2 class="mt-5 text-lg font-bold text-slate-900">A conversation starts here</h2>
                <p class="mt-2 max-w-xs text-sm text-slate-500">Choose someone from your school to send a private message. Replies appear automatically while this page is open.</p>
            </section>
        @endif
    </div>
</div>
