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
        class="grid grid-cols-[minmax(330px,1fr)_110px_120px_180px] items-center px-5 py-3.5 transition {{ $canManageAccounts ? 'cursor-pointer hover:bg-amber-50/60 focus:bg-amber-50/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-400' : '' }} {{ $editingAccountId === $account->id ? 'bg-amber-50 ring-2 ring-inset ring-amber-400' : '' }} {{ !$account->is_active ? 'bg-slate-50 opacity-60' : '' }}">
        <div class="flex min-w-0 items-center gap-2" style="padding-left: {{ min($depth, 6) * 24 }}px">
            @if($hasChildren)
                <button type="button" wire:click.stop x-on:click.stop="open = !open" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg border border-slate-200 bg-white text-slate-500 shadow-sm hover:border-amber-300 hover:text-slate-900" :aria-expanded="open">
                    <svg class="h-3.5 w-3.5 transition-transform" :class="open && 'rotate-90'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" /></svg>
                </button>
            @else
                <span class="flex h-7 w-7 shrink-0 items-center justify-center text-slate-300">{{ $depth ? '└' : '•' }}</span>
            @endif
            <span class="shrink-0 rounded-lg bg-slate-100 px-2 py-1 font-mono text-xs font-black text-slate-700">{{ $account->code }}</span>
            <span class="min-w-0"><span class="block truncate {{ $hasChildren ? 'font-black text-slate-900' : 'font-bold text-slate-800' }}">{{ $account->name }}</span><span class="block truncate text-[10px] text-slate-400">{{ $hasChildren ? $children->count().' subaccount'.($children->count() === 1 ? '' : 's') : ($account->subtype ? str($account->subtype)->headline() : 'Ledger account') }}</span></span>
        </div>
        <span class="text-xs font-bold text-slate-600">{{ ucfirst($account->normal_balance) }}</span>
        <span>@if($account->is_control_account)<span class="rounded-lg bg-violet-100 px-2.5 py-1 text-[10px] font-black uppercase text-violet-800">Control</span>@elseif($account->accepts_postings)<span class="rounded-lg bg-emerald-50 px-2.5 py-1 text-[10px] font-black uppercase text-emerald-700">Posting</span>@else<span class="rounded-lg bg-slate-100 px-2.5 py-1 text-[10px] font-black uppercase text-slate-600">Header</span>@endif</span>
        <span class="text-right"><span class="mr-3 inline-flex items-center gap-1.5 text-[11px] font-bold {{ $account->is_active ? 'text-emerald-700' : 'text-slate-400' }}"><span class="h-1.5 w-1.5 rounded-full {{ $account->is_active ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>{{ $account->is_active ? 'Active' : 'Archived' }}</span>@if($canManageAccounts)<button type="button" wire:click.stop="toggleAccount({{ $account->id }})" class="text-[11px] font-black text-slate-500 hover:text-slate-900">{{ $account->is_active ? 'Archive' : 'Reactivate' }}</button>@endif</span>
    </div>

    @if($hasChildren)
        <div x-show="open">
            @foreach($children as $child)
                @include('livewire.partials.account-tree-row', ['account' => $child, 'accountsByParent' => $accountsByParent, 'depth' => $depth + 1])
            @endforeach
        </div>
    @endif
</div>
