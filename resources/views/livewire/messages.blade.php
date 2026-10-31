<div wire:poll.2s.visible class="messages-workspace flex min-h-0 w-full flex-1 flex-col gap-3" x-data="{ uploading: false }"
    x-on:livewire-upload-start="uploading = true" x-on:livewire-upload-finish="uploading = false" x-on:livewire-upload-error="uploading = false" x-on:livewire-upload-cancel="uploading = false">
    <header class="relative shrink-0 overflow-hidden rounded-2xl p-4 text-white shadow-lg ring-2 ring-yellow-400/20" style="background: linear-gradient(135deg, #252641 0%, #3a3d6b 100%);">
        <div class="relative z-10 flex flex-wrap items-center justify-between gap-3">
            <div class="min-w-0 flex-1">
                <h1 class="text-2xl font-bold tracking-tight text-amber-300">Messages</h1>
                <p class="mt-1 text-sm leading-relaxed text-slate-400">Private conversations with people at {{ $user->school->name }}.</p>
            </div>
            <div class="shrink-0 text-right">
                <span class="inline-flex items-center gap-2 text-xs text-slate-300" wire:offline.class="hidden"><span class="h-2 w-2 rounded-full bg-emerald-400"></span>Auto updates · every 2 seconds</span>
                <span wire:offline class="block text-xs text-amber-300">You’re offline. Reconnect to chat.</span>
            </div>
        </div>
        <div class="pointer-events-none absolute -bottom-10 -right-6 h-40 w-40 rounded-full bg-yellow-400/10"></div>
        <div class="pointer-events-none absolute -top-10 right-16 h-24 w-24 rounded-full bg-yellow-400/10"></div>
    </header>

    <div class="grid min-h-0 flex-1 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm md:grid-cols-[280px_minmax(0,1fr)]">
        <aside class="min-h-0 flex-col border-r border-slate-100 {{ $peer ? 'hidden md:flex' : 'flex' }}">
            <div class="border-b border-slate-100 p-4">
                <h2 class="font-semibold text-slate-900">Your school</h2>
                <label for="chat-search" class="sr-only">Find someone by name</label>
                <input id="chat-search" type="search" wire:model.live.debounce.300ms="search" placeholder="Find someone by name…" maxlength="100" class="mt-3 w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2.5 text-sm focus:border-amber-400 focus:outline-none">
                <p class="mt-2 text-xs text-slate-400">Recent chats first. Search to find more people.</p>
            </div>
            <div class="min-h-0 flex-1 overflow-y-auto p-2">
                @forelse($contacts as $contact)
                    <button type="button" wire:key="contact-{{ $contact->id }}" wire:click="openConversation({{ $contact->id }})" :disabled="uploading" wire:loading.attr="disabled" wire:target="send" class="mb-1 flex w-full items-center gap-3 rounded-xl p-3 text-left transition hover:bg-amber-50 {{ $peerId === $contact->id ? 'bg-amber-50 ring-1 ring-inset ring-amber-200' : '' }}">
                        @php($contactPhoto = $contact->avatarUrl())
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-100 font-semibold text-slate-600">@if($contactPhoto)<img src="{{ $contactPhoto }}" alt="{{ $contact->name }}" width="40" height="40" loading="lazy" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($contact->name, 0, 1)) }}@endif</span>
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
                    <button type="button" wire:click="closeConversation" :disabled="uploading" wire:loading.attr="disabled" wire:target="send" class="rounded-lg border px-2 py-1 text-sm md:hidden" aria-label="Back to conversations">←</button>
                    @php($peerPhoto = $peer->avatarUrl())
                    <span class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-amber-100 font-semibold text-amber-800">@if($peerPhoto)<img src="{{ $peerPhoto }}" alt="{{ $peer->name }}" width="40" height="40" class="h-full w-full object-cover">@else{{ mb_strtoupper(mb_substr($peer->name, 0, 1)) }}@endif</span>
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
                                @if($message->body !== '')<p class="whitespace-pre-wrap break-words text-sm" style="overflow-wrap: anywhere;">{{ $message->body }}</p>@endif
                                @foreach($message->attachments ?? [] as $index => $file)
                                    @php($fileUrl = route('messages.attachments', ['message' => $message->id, 'attachment' => $index]))
                                    <a href="{{ $fileUrl }}" class="mt-2 block overflow-hidden rounded-xl border border-current/15" download>
                                        @if(in_array($file['mime'], ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true))
                                            <img src="{{ $fileUrl }}?preview=1" alt="{{ $file['name'] }}" loading="lazy" class="max-h-56 w-full object-contain" style="min-width: 120px;">
                                        @endif
                                        <span class="flex items-center gap-3 p-3"><x-heroicon-o-document-arrow-down class="h-5 w-5 shrink-0" /><span class="min-w-0"><span class="block break-all text-xs font-semibold">{{ $file['name'] }}</span><span class="mt-1 block text-[10px] opacity-70">{{ number_format($file['size'] / 1024, 0) }} KB · Download</span></span></span>
                                    </a>
                                @endforeach
                                <p class="mt-2 text-right text-[10px] {{ $message->sender_id === $user->id ? 'text-slate-300' : 'text-slate-400' }}"><time datetime="{{ $message->created_at->toIso8601String() }}">{{ $message->created_at->format('d M, H:i') }}</time>@if($message->sender_id === $user->id) · {{ $message->read_at ? 'Read' : 'Sent' }}@endif</p>
                            </div>
                        </div>
                    @empty
                        <div class="flex h-full flex-col items-center justify-center text-center"><p class="font-semibold text-slate-700">Start a conversation with {{ $peer->name }}</p><p class="mt-2 text-sm text-slate-400">Send your first message below.</p></div>
                    @endforelse
                </div>
                @if($sendAllowed)
                <form wire:submit="send" class="chat-composer shrink-0 border-t border-slate-100 bg-white p-3 sm:p-4">
                    @if($attachments)
                        <div class="mb-3 flex max-h-28 gap-2 overflow-auto pb-1">
                            @foreach($attachments as $index => $attachment)
                                @continue(! ($attachment instanceof \Illuminate\Http\UploadedFile))
                                <div wire:key="draft-file-{{ $uploadRevision }}-{{ $index }}" class="flex max-w-64 shrink-0 items-center gap-2 rounded-xl border border-slate-200 bg-slate-50 px-3 py-2">
                                    @if(in_array($attachment->getMimeType(), ['image/jpeg', 'image/png', 'image/webp', 'image/gif'], true) && $attachment->getSize() <= 10485760)
                                        <img src="{{ $attachment->temporaryUrl() }}" alt="Image attachment preview" class="h-12 w-12 shrink-0 rounded-lg object-cover">
                                    @else<x-heroicon-o-document-text class="h-8 w-8 shrink-0 text-slate-400" />@endif
                                    <span class="min-w-0"><span class="block truncate text-xs font-semibold text-slate-700">{{ $attachment->getClientOriginalName() }}</span><span class="block text-[10px] text-slate-400">{{ number_format($attachment->getSize() / 1024, 0) }} KB</span></span>
                                    <button type="button" wire:click="removeAttachment({{ $index }})" wire:loading.attr="disabled" wire:target="send" aria-label="Remove {{ $attachment->getClientOriginalName() }}" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-slate-400 hover:bg-red-50 hover:text-red-600"><x-heroicon-o-x-mark class="h-4 w-4" /></button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    <label for="chat-body" class="sr-only">Your message</label>
                    <div class="flex items-end gap-2 rounded-2xl border border-slate-200 bg-slate-50 p-2 shadow-sm focus-within:border-amber-400 focus-within:ring-2 focus-within:ring-amber-100">
                        <div class="flex shrink-0 items-center self-end" wire:key="upload-inputs-{{ $uploadRevision }}">
                            <input id="chat-documents" type="file" wire:model="uploads" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.txt,.csv" class="sr-only" :disabled="uploading" wire:loading.attr="disabled" wire:target="send">
                            <label for="chat-documents" title="Attach documents" class="flex h-10 w-9 cursor-pointer items-center justify-center rounded-xl text-slate-500 hover:bg-white hover:text-amber-700"><x-heroicon-o-paper-clip class="h-5 w-5" /><span class="sr-only">Attach documents</span></label>
                            <input id="chat-images" type="file" wire:model="uploads" multiple accept="image/jpeg,image/png,image/webp,image/gif" class="sr-only" :disabled="uploading" wire:loading.attr="disabled" wire:target="send">
                            <label for="chat-images" title="Attach images" class="flex h-10 w-9 cursor-pointer items-center justify-center rounded-xl text-slate-500 hover:bg-white hover:text-amber-700"><x-heroicon-o-photo class="h-5 w-5" /><span class="sr-only">Attach images</span></label>
                        </div>
                        <textarea id="chat-body" wire:model="body" rows="1" maxlength="5000" placeholder="Write a message…" wire:loading.attr="disabled" wire:target="send" x-on:input="$el.style.height = '44px'; $el.style.height = Math.min($el.scrollHeight, 96) + 'px'" x-on:chat-sent.window="$el.style.height = '44px'" class="min-w-0 flex-1 resize-none border-0 bg-transparent px-2 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-0"></textarea>
                        <button type="submit" :disabled="uploading" wire:loading.attr="disabled" wire:target="send" wire:offline.attr="disabled" title="Send message" aria-label="Send message" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-amber-400 text-slate-900 shadow-sm transition hover:bg-amber-500 disabled:cursor-not-allowed disabled:opacity-50"><x-heroicon-o-paper-airplane wire:loading.remove wire:target="send" class="h-5 w-5 -rotate-45" /><svg wire:loading wire:target="send" class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="3" opacity=".25" /><path d="M21 12a9 9 0 00-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round" /></svg><span class="sr-only">Send message</span></button>
                    </div>
                    <p x-show="uploading" x-cloak class="mt-2 text-xs text-amber-700" role="status">Uploading attachments…</p>
                    @error('body')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                    @error('attachments')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                    @error('uploads')<p class="mt-2 text-sm text-red-600" role="alert">{{ $message }}</p>@enderror
                    @foreach($errors->get('uploads.*') as $fileErrors)@foreach($fileErrors as $error)<p class="mt-2 text-sm text-red-600" role="alert">{{ $error }}</p>@endforeach @endforeach
                    @foreach($errors->get('attachments.*') as $fileErrors)@foreach($fileErrors as $error)<p class="mt-2 text-sm text-red-600" role="alert">{{ $error }}</p>@endforeach @endforeach
                    <p class="mt-2 text-[11px] text-slate-400">Up to 5 files · 10 MB each · Images, PDF, Office documents, TXT or CSV</p>
                </form>
                @else
                    <div class="shrink-0 border-t border-amber-200 bg-amber-50 px-4 py-4 text-sm text-amber-900" role="status">
                        Your school has turned off {{ $user->role === 'parent' ? 'parent' : 'student' }} messaging. You can still read and receive messages, but cannot send messages or attachments.
                    </div>
                @endif
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
