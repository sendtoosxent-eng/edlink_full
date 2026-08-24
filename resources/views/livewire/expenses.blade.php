<div class="space-y-6">

    {{-- Redesigned Header Banner --}}
    <div class="bg-slate-900 border border-slate-900 rounded-2xl p-6 shadow-xs">
        <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
            
            {{-- Title & Context --}}
            <div class="flex items-start gap-4">
                
                <div>
                    <div class="flex items-center gap-3 flex-wrap">
                        <h1 class="text-2xl font-extrabold text-amber-300 tracking-tight">Expenses Management</h1>
                        
                        {{-- Term Status Badge --}}
                        @if($this->selectedTerm)
                            @if($this->canEdit)
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200/80">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                                    Open Term
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-600 border border-gray-200">
                                    <svg class="w-3.5 h-3.5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                                    </svg>
                                    Closed Term (View Only)
                                </span>
                            @endif
                        @endif
                    </div>
                    <p class="text-sm text-slate-400 mt-1">
                        Track approved school operational expenses per term. Pending items remain in Ledger & Reconciliation until another authorized user approves them.
                    </p>
                </div>
            </div>

            {{-- Header Right Controls: Term Switcher Dropdown --}}
            <div class="flex items-center gap-3 bg-amber-400 p-2 rounded-xl border border-amber-300 self-start lg:self-center">
                <span class="text-xs font-semibold text-slate-500 pl-2 uppercase tracking-wider">Academic Term:</span>
                <div class="relative min-w-[210px]">
                    <select wire:model.live="termId" class="w-full appearance-none bg-slate-800 border border-slate-600 text-slate-300 text-sm font-medium rounded-lg pl-3 pr-8 py-2 shadow-xs focus:border-amber-500 focus:ring-2 focus:ring-amber-400/20 focus:outline-none transition cursor-pointer">
                        @forelse($terms as $term)
                            <option value="{{ $term->id }}">
                                {{ $term->name }}, {{ $term->year }} {{ $term->is_current ? '• (Current)' : ($term->locked ? '• (Locked)' : '• (Closed)') }}
                            </option>
                        @empty
                            <option value="">No terms recorded</option>
                        @endforelse
                    </select>
                    <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-2.5 text-gray-400">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </div>
                </div>
            </div>

        </div>
    </div>

    {{-- Flash Notifications --}}
    @if (session('status'))
        <div class="flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 text-sm rounded-xl p-4 shadow-xs">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif
    @if (session('error'))
        <div class="flex items-center gap-3 bg-rose-50 border border-rose-200 text-rose-800 text-sm rounded-xl p-4 shadow-xs">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    {{-- Main Grid: Summary & Form --}}
    <div class="grid lg:grid-cols-12 gap-6 items-start">

        {{-- Total Summary Card --}}
        <div class="lg:col-span-4 bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6 space-y-6">
            <div>
                <span class="text-xs font-semibold uppercase tracking-wider text-gray-400">Term Expenditure</span>
                <div class="flex items-baseline gap-2 mt-1">
                    <span class="text-xs font-bold text-gray-400">UGX</span>
                    <span class="text-3xl font-extrabold text-gray-900 tracking-tight">{{ number_format($total) }}</span>
                </div>
            </div>

            @if($totalsByCategory->isNotEmpty())
                <div class="pt-4 border-t border-gray-100 space-y-3.5">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-400 mb-2">Category Breakdown</h3>
                    @foreach($totalsByCategory as $cat => $amt)
                        @php
                            $percentage = $total > 0 ? min(100, round(($amt / $total) * 100)) : 0;
                        @endphp
                        <div class="space-y-1">
                            <div class="flex items-center justify-between text-xs">
                                <span class="font-medium text-gray-600">{{ $cat }}</span>
                                <span class="font-semibold text-gray-900">{{ number_format($amt) }} <span class="text-gray-400 font-normal">({{ $percentage }}%)</span></span>
                            </div>
                            <div class="w-full h-1.5 bg-gray-100 rounded-full overflow-hidden">
                                <div class="h-full bg-yellow-400 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Record Expense Form Card --}}
        <div class="lg:col-span-8 bg-white rounded-2xl shadow-sm border border-gray-200/80 p-6">
            <div class="flex items-center justify-between mb-5 pb-3 border-b border-gray-100">
                <h2 class="font-semibold text-gray-900 text-base">Record New Expense</h2>
                @if(!$this->canEdit)
                    <span class="text-xs text-amber-700 bg-amber-50 px-2.5 py-1 rounded-md font-medium">Read-only mode</span>
                @endif
            </div>

            @if($this->canEdit)
                <form wire:submit="add" class="space-y-5">
                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Category <span class="text-rose-500">*</span></label>
                            <select wire:model="category" class="w-full bg-gray-50/50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3.5 py-2.5 focus:bg-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-400/20 focus:outline-none transition">
                                @foreach(\App\Models\Expense::CATEGORIES as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Date <span class="text-rose-500">*</span></label>
                            <input type="date" wire:model="expense_date" class="w-full bg-gray-50/50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3.5 py-2.5 focus:bg-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-400/20 focus:outline-none transition">
                            @error('expense_date') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Receipt / Voucher Number</label>
                            <input type="text" wire:model="reference_number" placeholder="e.g. PV-2026-001" autocomplete="off" class="w-full bg-gray-50/50 border border-gray-200 text-gray-800 text-sm font-mono uppercase rounded-xl px-3.5 py-2.5 focus:bg-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-400/20 focus:outline-none transition placeholder:normal-case placeholder:font-sans">
                            @error('reference_number') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-700 mb-1.5">Amount (UGX) <span class="text-rose-500">*</span></label>
                            <input type="number" step="0.01" min="0" wire:model="amount" placeholder="0.00" class="w-full bg-gray-50/50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3.5 py-2.5 focus:bg-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-400/20 focus:outline-none transition">
                            @error('amount') <span class="text-rose-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-medium text-gray-700 mb-1.5">Description <span class="text-gray-400 font-normal">(optional)</span></label>
                        <input type="text" wire:model="description" placeholder="e.g. March electricity bill or science lab supplies" class="w-full bg-gray-50/50 border border-gray-200 text-gray-800 text-sm rounded-xl px-3.5 py-2.5 focus:bg-white focus:border-yellow-500 focus:ring-2 focus:ring-yellow-400/20 focus:outline-none transition">
                    </div>

                    <div class="pt-2 flex justify-end">
                        <button type="submit" wire:loading.attr="disabled" wire:target="add"
                            class="inline-flex items-center gap-2 bg-yellow-400 hover:bg-yellow-500 text-gray-900 font-semibold text-sm px-6 py-2.5 rounded-xl shadow-xs transition active:scale-[0.99] disabled:opacity-60 cursor-pointer">
                            <span wire:loading wire:target="add"><x-edlink-loader size="16" /></span>
                            <svg wire:loading.remove wire:target="add" class="w-4 h-4 text-gray-900" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" /></svg>
                            <span>Add Expense</span>
                        </button>
                    </div>
                </form>
            @else
                <div class="py-8 text-center bg-gray-50/60 rounded-xl border border-dashed border-gray-200">
                    <svg class="w-8 h-8 text-gray-400 mx-auto mb-2" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                    <p class="text-sm font-medium text-gray-600">This term is closed</p>
                    <p class="text-xs text-gray-400 mt-1">Expenses can only be submitted for the active term.</p>
                </div>
            @endif
        </div>

    </div>

    {{-- Expense Records Table --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-200/80 overflow-hidden">
        <div class="px-6 py-4 border-b border-gray-100 flex items-center justify-between">
            <div><h2 class="font-semibold text-gray-900 text-base">Approved Expense Log</h2><p class="mt-0.5 text-[11px] text-gray-400">Pending and rejected expenses are excluded from this list and all totals.</p></div>
            <span class="text-xs text-gray-400 font-medium">{{ $expenses->total() ?? count($expenses) }} Entries</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-sm text-left">
                <thead>
                    <tr class="bg-gray-50/70 text-gray-500 text-xs uppercase tracking-wider border-b border-gray-100">
                        <th class="px-6 py-3.5 font-semibold">Date</th>
                        <th class="px-6 py-3.5 font-semibold">Voucher Ref</th>
                        <th class="px-6 py-3.5 font-semibold">Category</th>
                        <th class="px-6 py-3.5 font-semibold">Description</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Amount (UGX)</th>
                        <th class="px-6 py-3.5 font-semibold text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($expenses as $expense)
                        <tr class="hover:bg-gray-50/70 transition-colors">
                            <td class="px-6 py-4 text-gray-600 whitespace-nowrap font-medium">
                                {{ $expense->expense_date->format('d M, Y') }}
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                @if($expense->reference_number)
                                    <span class="font-mono text-xs font-semibold px-2 py-1 bg-gray-100 text-gray-700 rounded-md border border-gray-200/60">
                                        {{ $expense->reference_number }}
                                    </span>
                                @else
                                    <span class="text-gray-300">—</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center text-xs font-medium px-2.5 py-1 rounded-full bg-slate-100 text-slate-700">
                                    {{ $expense->category }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-gray-600 max-w-xs truncate">
                                {{ $expense->description ?: '—' }}
                            </td>
                            <td class="px-6 py-4 text-right font-semibold text-gray-900 whitespace-nowrap">
                                {{ number_format($expense->amount) }}
                            </td>
                            <td class="px-6 py-4 text-right whitespace-nowrap">
                                @if($this->canEdit)
                                    @if($deletingId === $expense->id)
                                        <div class="inline-flex items-center gap-1.5 bg-rose-50 border border-rose-200 rounded-lg p-1">
                                            <span class="text-xs text-rose-700 font-medium px-1">Confirm?</span>
                                            <button wire:click="delete({{ $expense->id }})" class="text-xs bg-rose-600 text-white font-medium px-2.5 py-1 rounded-md hover:bg-rose-700 transition">Delete</button>
                                            <button wire:click="cancelDelete" class="text-xs bg-white border border-gray-200 text-gray-600 font-medium px-2.5 py-1 rounded-md hover:bg-gray-50 transition">Cancel</button>
                                        </div>
                                    @else
                                        <button wire:click="confirmDelete({{ $expense->id }})" class="p-1.5 text-gray-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-colors" title="Delete Expense">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    @endif
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <div class="max-w-xs mx-auto space-y-2">
                                    <div class="w-10 h-10 bg-gray-100 rounded-full flex items-center justify-center mx-auto text-gray-400">
                                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2-2 4 4m4-7a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </div>
                                    <p class="text-sm font-medium text-gray-900">No expenses found</p>
                                    <p class="text-xs text-gray-400">There are no expense records logged for the selected term yet.</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50/50">
                {{ $expenses->links() }}
            </div>
        @endif
    </div>

</div>
