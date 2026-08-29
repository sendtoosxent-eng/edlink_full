@php
    $children = $accountsByParent->get($account->id, collect())->sortBy('code');
    $hasChildren = $children->isNotEmpty();
    $canManageAccounts = auth()->user()->hasPermission('accounting.accounts.manage');
@endphp

<div x-data="{ open: true }" wire:key="ledger-account-{{ $account->id }}">
    <div
        @if($canManageAccounts)
            wire:click="editAccount({{ $account->id }})"
            x-on:click="document.getElementById('account-editor')?.scrollIntoView({ behavior: 'smooth', block: 'start' })"
            x-on:keydown.enter.prevent="$wire.editAccount({{ $account->id }})"
            tabindex="0"
            role="button"
            aria-label="Edit {{ $account->code }} {{ $account->name }}"
        @endif
        class="group flex items-center rounded-lg px-2 py-1.5 transition {{ $canManageAccounts ? 'cursor-pointer hover:bg-amber-50 focus:bg-amber-50 focus:outline-none' : '' }} {{ $editingAccountId === $account->id ? 'bg-amber-100 ring-1 ring-amber-300' : '' }} {{ !$account->is_active ? 'opacity-50' : '' }}">
        <div class="flex min-w-0 items-center gap-2" style="padding-left: {{ min($depth, 6) * 24 }}px">
            @if($hasChildren)
                <button type="button" wire:click.stop x-on:click.stop="open = !open" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-amber-300 hover:text-slate-900" :aria-expanded="open">
                    <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
            @else
                <span class="flex h-7 w-7 shrink-0 items-center justify-center"><span class="h-1.5 w-1.5 rounded-full {{ $depth ? 'bg-slate-300' : 'bg-amber-400' }}"></span></span>
            @endif
            <span class="font-mono text-sm font-black text-slate-800">{{ $account->code }}</span><span class="mx-1 text-slate-400">:</span>
            <span class="min-w-0 truncate text-sm {{ $hasChildren ? 'font-black text-slate-900' : 'font-semibold text-slate-700' }}">{{ $account->name }}</span>
        </div>
    </div>

    @if($hasChildren)
        <div x-show="open">
            @foreach($children as $child)
                @include('livewire.partials.account-tree-row', ['account' => $child, 'accountsByParent' => $accountsByParent, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
