<div>
    <div class="mb-8">
        <h1 class="text-2xl font-semibold text-darken">Expenses</h1>
        <p class="text-gray-500 text-sm mt-1">Tracked per term. Only the currently open term can be edited — closed terms are view-only.</p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 text-sm rounded-lg px-4 py-3 mb-6">{{ session('status') }}</div>
    @endif
    @if (session('error'))
        <div class="bg-red-50 border border-red-200 text-red-700 text-sm rounded-lg px-4 py-3 mb-6">{{ session('error') }}</div>
    @endif

    {{-- Term selector --}}
    <div class="flex flex-wrap items-center justify-between gap-3 mb-6">
        <select wire:model.live="termId" class="text-sm border border-gray-200 rounded-lg px-3 py-2 text-gray-600 focus:outline-none focus:ring-2 focus:ring-yellow-400">
            @forelse($terms as $term)
                <option value="{{ $term->id }}">
                    {{ $term->name }}, {{ $term->year }} {{ $term->is_current ? '(Open)' : ($term->locked ? '(Closed — locked)' : '(Closed)') }}
                </option>
            @empty
                <option value="">No terms yet</option>
            @endforelse
        </select>

        @if(!$this->canEdit && $this->selectedTerm)
            <span class="text-xs bg-gray-100 text-gray-500 px-3 py-1.5 rounded-full inline-flex items-center space-x-1.5">
                <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" /></svg>
                <span>View only — this term is closed</span>
            </span>
        @endif
    </div>

    <div class="grid lg:grid-cols-3 gap-6 mb-8">

        {{-- Summary --}}
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-darken mb-1">Total this term</h2>
            <p class="text-3xl font-bold text-darken mt-2">UGX {{ number_format($total) }}</p>

            @if($totalsByCategory->isNotEmpty())
                <div class="mt-5 space-y-2">
                    @foreach($totalsByCategory as $cat => $amt)
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-gray-500">{{ $cat }}</span>
                            <span class="font-medium text-darken">{{ number_format($amt) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Add expense --}}
        <div class="lg:col-span-2 bg-white rounded-2xl shadow-sm border border-gray-100 p-6">
            <h2 class="font-semibold text-darken mb-4">Record an expense</h2>

            @if($this->canEdit)
                <form wire:submit="add" class="space-y-4">
                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Category</label>
                            <select wire:model="category" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                                @foreach(\App\Models\Expense::CATEGORIES as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Date</label>
                            <input type="date" wire:model="expense_date" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            @error('expense_date') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Receipt / voucher number</label>
                            <input type="text" wire:model="reference_number" placeholder="e.g. PV-2026-001" autocomplete="off" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 uppercase focus:outline-none focus:ring-2 focus:ring-yellow-400">
                            @error('reference_number') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Amount (UGX)</label>
                        <input type="number" step="0.01" min="0" wire:model="amount" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                        @error('amount') <span class="text-red-500 text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Description (optional)</label>
                        <input type="text" wire:model="description" placeholder="e.g. March electricity bill" class="w-full border border-gray-300 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    </div>

                    <button type="submit" wire:loading.attr="disabled" wire:target="add"
                        class="inline-flex items-center space-x-2 bg-yellow-500 text-darken font-semibold px-6 py-2.5 rounded-full hover:bg-yellow-400 transition disabled:opacity-60">
                        <span wire:loading wire:target="add"><x-edlink-loader size="16" /></span>
                        <span>Add expense</span>
                    </button>
                </form>
            @else
                <p class="text-gray-400 text-sm">This term is closed — expenses can only be added to the currently open term.</p>
            @endif
        </div>
    </div>

    {{-- List --}}
    <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-gray-400 text-xs uppercase tracking-wide border-b border-gray-100">
                        <th class="px-5 py-3">Date</th>
                        <th class="px-5 py-3">Receipt / voucher</th>
                        <th class="px-5 py-3">Category</th>
                        <th class="px-5 py-3">Description</th>
                        <th class="px-5 py-3">Amount</th>
                        <th class="px-5 py-3"></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($expenses as $expense)
                        <tr class="border-b border-gray-50 hover:bg-gray-50/60">
                            <td class="px-5 py-3 text-gray-500">{{ $expense->expense_date->format('d M Y') }}</td>
                            <td class="px-5 py-3 font-mono text-xs font-semibold text-gray-700">{{ $expense->reference_number ?: '-' }}</td>
                            <td class="px-5 py-3">
                                <span class="text-xs font-medium px-2.5 py-1 rounded-full bg-gray-100 text-gray-600">{{ $expense->category }}</span>
                            </td>
                            <td class="px-5 py-3 text-gray-500">{{ $expense->description ?: '—' }}</td>
                            <td class="px-5 py-3 font-medium text-darken">{{ number_format($expense->amount) }}</td>
                            <td class="px-5 py-3 text-right">
                                @if($this->canEdit)
                                    @if($deletingId === $expense->id)
                                        <span class="text-xs text-gray-500 mr-2">Remove?</span>
                                        <button wire:click="delete({{ $expense->id }})" class="text-xs bg-red-500 text-white px-3 py-1 rounded-full hover:bg-red-600">Yes</button>
                                        <button wire:click="cancelDelete" class="text-xs border border-gray-300 text-gray-600 px-3 py-1 rounded-full hover:bg-gray-50 ml-1">Cancel</button>
                                    @else
                                        <button wire:click="confirmDelete({{ $expense->id }})" class="text-gray-400 hover:text-red-500 transition-colors">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                        </button>
                                    @endif
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="px-5 py-10 text-center text-gray-400 text-sm">No expenses recorded for this term.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($expenses->hasPages())
            <div class="p-5 border-t border-gray-100">{{ $expenses->links() }}</div>
        @endif
    </div>
</div>