<x-app-layout>
    <div class="space-y-6">
        <header class="relative overflow-hidden rounded-2x1 bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 p-6 sm:p-8">
            <div class="relative z-10 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                
            <h1 class="mt-3 text-2x1 font-black sm:text-3xl text-amber-300">Ledger reconciliation</h1>
            <p class="mt-1.5 max-w-2x1 text-sm leading-relaxed text-slate-500">Compare your bank/cash statement balance with the posted ledger balance.</p>
                </div>
            </div>
            
        </header>
        @if(session('status'))<div class="rounded-xl bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
        @if($errors->any())<div class="rounded-xl bg-rose-50 p-3 text-rose-800">{{ $errors->first() }}</div>@endif
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">@foreach($accounts as $account)<div class="rounded-2xl border bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-400">{{ str_replace('_',' ',$account->type) }}</p><p class="font-bold">{{ $account->name }}</p><p class="mt-2 text-xl font-black">UGX {{ number_format($account->balance(),2) }}</p></div>@endforeach</section>
        <section class="grid gap-5 lg:grid-cols-2">
        <form method="POST" action="{{ route('finance.accounts.store') }}" class="rounded-2xl bg-white p-5 shadow">@csrf<h2 class="font-black">Add financial account</h2><div class="mt-3 grid gap-3 sm:grid-cols-3"><input name="name" required placeholder="Account name" class="rounded-xl border-slate-300"><select name="type" class="rounded-xl border-slate-300"><option value="bank">Bank</option><option value="cash">Cash</option><option value="mobile_money">Mobile money</option><option value="petty_cash">Petty cash</option></select><input name="opening_balance" type="number" step=".01" value="0" required class="rounded-xl border-slate-300"></div><button class="mt-3 rounded-xl bg-slate-900 px-4 py-2 font-bold text-white">Create account</button></form>
        <form method="POST" action="{{ route('finance.transfers.store') }}" class="rounded-2xl bg-white p-5 shadow">@csrf<h2 class="font-black">Transfer between accounts</h2><div class="mt-3 grid gap-3 sm:grid-cols-2"><select name="from_account_id" required class="rounded-xl border-slate-300"><option value="">From account</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select><select name="to_account_id" required class="rounded-xl border-slate-300"><option value="">To account</option>@foreach($accounts as $a)<option value="{{ $a->id }}">{{ $a->name }}</option>@endforeach</select><input name="amount" type="number" min=".01" step=".01" required placeholder="Amount" class="rounded-xl border-slate-300"><input name="transfer_date" type="date" value="{{ now()->toDateString() }}" required class="rounded-xl border-slate-300"><input name="reference" placeholder="Reference" class="rounded-xl border-slate-300"><input name="notes" placeholder="Notes" class="rounded-xl border-slate-300"></div><button class="mt-3 rounded-xl bg-indigo-700 px-4 py-2 font-bold text-white">Submit for approval</button></form></section>
        @if($transfers->isNotEmpty())<section class="overflow-x-auto rounded-2xl bg-white shadow"><table class="w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-3">Transfer</th><th>Amount</th><th>Status</th><th>Control</th></tr></thead><tbody>@foreach($transfers as $transfer)<tr class="border-t"><td class="p-3">{{ $transfer->fromAccount?->name }} to {{ $transfer->toAccount?->name }}</td><td>UGX {{ number_format($transfer->amount,2) }}</td><td>{{ ucfirst($transfer->status) }}</td><td>@if($transfer->status==='pending')<form method="POST" action="{{ route('finance.transfers.approve',$transfer) }}">@csrf<button class="font-bold text-emerald-700">Approve transfer</button></form>@endif</td></tr>@endforeach</tbody></table></section>@endif
        <form method="POST" action="{{ route('finance.ledger.reconcile') }}" class="rounded-2xl bg-white p-5 shadow">@csrf
            <div class="grid gap-3 md:grid-cols-5">
                <label><span class="mb-1 block text-xs font-bold text-slate-600">Account</span><select name="financial_account_id" required class="w-full rounded-xl border-slate-300">@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst(str_replace('_',' ',$account->type)) }})</option>@endforeach</select></label>
                <label><span class="mb-1 block text-xs font-bold text-slate-600">Statement ending date</span><input type="date" name="period_ending" value="{{ old('period_ending') }}" required class="w-full rounded-xl border-slate-300"></label>
                <label><span class="mb-1 block text-xs font-bold text-slate-600">Bank/cash statement balance</span><input type="number" step="0.01" name="statement_balance" value="{{ old('statement_balance') }}" placeholder="0.00" required class="w-full rounded-xl border-slate-300"></label>
                <label><span class="mb-1 block text-xs font-bold text-slate-600">Notes</span><input name="notes" value="{{ old('notes') }}" placeholder="Reason for any difference" class="w-full rounded-xl border-slate-300"></label>
                <button class="mt-5 rounded-xl bg-slate-900 px-4 py-2 font-bold text-white">Save reconciliation</button>
            </div>
        
            <p class="mt-3 text-xs text-slate-500">Edlink compares the statement balance with posted ledger credits minus posted ledger debits up to the selected date.</p>
        </form>

        @if($reconciliations->isNotEmpty())
            @php($latest = $reconciliations->first())
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-400">Statement balance</p><p class="mt-2 text-2xl font-black text-slate-900">UGX {{ number_format($latest->statement_balance, 2) }}</p></div>
                <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm"><p class="text-xs font-bold uppercase text-slate-400">Edlink ledger balance</p><p class="mt-2 text-2xl font-black text-slate-900">UGX {{ number_format($latest->ledger_balance, 2) }}</p></div>
                <div class="rounded-2xl border {{ (float) $latest->difference === 0.0 ? 'border-emerald-200 bg-emerald-50' : 'border-rose-200 bg-rose-50' }} p-5 shadow-sm"><p class="text-xs font-bold uppercase {{ (float) $latest->difference === 0.0 ? 'text-emerald-600' : 'text-rose-600' }}">Difference</p><p class="mt-2 text-2xl font-black {{ (float) $latest->difference === 0.0 ? 'text-emerald-800' : 'text-rose-800' }}">UGX {{ number_format($latest->difference, 2) }}</p><p class="mt-1 text-xs {{ (float) $latest->difference === 0.0 ? 'text-emerald-700' : 'text-rose-700' }}">{{ (float) $latest->difference === 0.0 ? 'Balances agree.' : 'Investigate missing, delayed, duplicated, or incorrect entries.' }}</p></div>
            </section>
            @if((float) $latest->ledger_balance === 0.0 && (float) $latest->statement_balance !== 0.0)
                <div class="rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900"><strong>No posted ledger balance was found by {{ $latest->period_ending->format('d M Y') }}.</strong> Confirm that fee payments, expenses, and payroll transactions have created or approved their ledger entries.</div>
            @endif
            <section class="overflow-hidden rounded-2xl bg-white shadow">
                <div class="border-b border-slate-100 p-5"><h2 class="font-black text-slate-900">Reconciliation history</h2></div>
                <div class="overflow-x-auto"><table class="w-full text-sm"><thead class="bg-slate-50 text-left text-xs uppercase text-slate-500"><tr><th class="p-3">Period ending</th><th>Statement</th><th>Ledger</th><th>Difference</th><th>Notes</th><th>Reconciled</th></tr></thead><tbody>
                    @foreach($reconciliations as $reconciliation)<tr class="border-t"><td class="p-3 font-semibold">{{ $reconciliation->period_ending->format('d M Y') }}</td><td>UGX {{ number_format($reconciliation->statement_balance, 2) }}</td><td>UGX {{ number_format($reconciliation->ledger_balance, 2) }}</td><td class="font-bold {{ (float) $reconciliation->difference === 0.0 ? 'text-emerald-700' : 'text-rose-700' }}">UGX {{ number_format($reconciliation->difference, 2) }}</td><td>{{ $reconciliation->notes ?: 'â€”' }}</td><td>{{ $reconciliation->reconciled_at?->format('d M Y H:i') ?: '—' }}<div class="text-xs">{{ $reconciliation->account?->name }} · {{ ucfirst($reconciliation->status) }}</div>@if($reconciliation->status === 'closed')<form method="POST" action="{{ route('finance.reconciliations.reopen',$reconciliation) }}" class="mt-1 flex gap-1">@csrf<input name="reason" required minlength="8" placeholder="Reopen reason" class="w-32 rounded border-slate-300 text-xs"><button class="text-amber-700">Reopen</button></form>@endif</td></tr>@endforeach
                </tbody></table></div>
            </section>
        @else
            <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">No reconciliation has been saved yet.</div>
        @endif
        <div class="overflow-x-auto rounded-2xl bg-white shadow"><table class="w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-3">Reference</th><th>Type</th><th>Direction</th><th>Amount</th><th>Status</th><th>Controls</th></tr></thead><tbody>
        @forelse($entries as $entry)<tr class="border-t"><td class="p-3 font-mono">{{ $entry->reference }}</td><td>{{ $entry->entry_type }}</td><td>{{ $entry->direction }}</td><td>{{ number_format($entry->amount, 2) }}</td><td>{{ ucfirst($entry->status) }}</td><td class="py-2">
            @if($entry->status === 'pending')<form class="inline" method="POST" action="{{ route('finance.ledger.approve',$entry) }}">@csrf<button class="text-emerald-700">Approve</button></form>@endif @if($entry->status === 'pending')<form class="inline-flex gap-1" method="POST" action="{{ route('finance.ledger.reject',$entry) }}">@csrf<input name="reason" required minlength="8" placeholder="Rejection reason" class="w-36 rounded border-slate-300 text-xs"><button class="text-rose-700">Reject</button></form>@endif
            @if($entry->status === 'posted' && !$entry->reversal_of_id)<form class="inline-flex gap-1" method="POST" action="{{ route('finance.ledger.reverse',$entry) }}">@csrf<input name="reason" required minlength="8" placeholder="Reversal reason" class="w-40 rounded border-slate-300 text-xs"><button class="text-red-700">Reverse</button></form>@endif
        </td></tr>@empty<tr><td colspan="6" class="p-8 text-center text-slate-500">No ledger entries yet.</td></tr>@endforelse
        </tbody></table></div>{{ $entries->links() }}
    </div>
</x-app-layout>


