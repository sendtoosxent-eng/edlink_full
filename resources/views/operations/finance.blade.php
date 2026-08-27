<x-app-layout>
    <div class="space-y-6">

        <!-- HEADER BANNER -->
        <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
            <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div class="flex items-start gap-4">
                   
                    <div>
                        <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">Account Reconciliation</h1>
                        <p class="mt-1 text-xs sm:text-sm text-slate-400 max-w-xl">
                            Compare bank and cash statement balances with posted general ledger records.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Ambient Glow -->
            <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
        </header>

        <!-- ALERTS -->
        @if(session('status'))
            <div class="flex items-center gap-2 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-xs sm:text-sm font-semibold text-emerald-800 shadow-sm">
                <svg class="h-5 w-5 text-emerald-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('status') }}</span>
            </div>
        @endif

        @if($errors->any())
            <div class="flex items-center gap-2 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-xs sm:text-sm font-semibold text-rose-800 shadow-sm">
                <svg class="h-5 w-5 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ $errors->first() }}</span>
            </div>
        @endif

        <!-- FINANCIAL ACCOUNTS CARDS -->
        <section class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach($accounts as $account)
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm transition hover:shadow-md">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">{{ str_replace('_',' ',$account->type) }}</span>
                    <h3 class="text-sm font-bold text-slate-900 mt-0.5 truncate">{{ $account->name }}</h3>
                    <p class="mt-3 font-mono text-xl font-extrabold text-slate-900">
                        <span class="text-xs font-bold text-slate-400 font-sans mr-0.5">UGX</span>{{ number_format($account->balance(), 2) }}
                    </p>
                </div>
            @endforeach
        </section>

        <!-- MANAGEMENT FORMS: CREATE ACCOUNT & TRANSFER -->
        <section class="grid gap-6 lg:grid-cols-2">
            
            <!-- Add Account Form -->
            <form method="POST" action="{{ route('finance.accounts.store') }}" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm flex flex-col justify-between">
                @csrf
                <div>
                    <div class="border-b border-slate-100 pb-3 mb-4">
                        <h2 class="text-base font-extrabold text-slate-900">Add Financial Account</h2>
                        <p class="text-xs text-slate-500">Register a new bank account or cash vault.</p>
                    </div>

                    <div class="grid gap-4 sm:grid-cols-3">
                        <div class="sm:col-span-3">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Account Name</label>
                            <input name="name" required placeholder="e.g. Stanbic Operating Account" class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Account Type</label>
                            <div class="relative">
                                <select name="type" class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-bold text-slate-800 transition">
                                    <option value="bank">Bank</option>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="petty_cash">Petty Cash</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Opening Bal.</label>
                            <input name="opening_balance" type="number" step=".01" value="0" required class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-mono font-bold text-slate-800 transition">
                        </div>
                    </div>
                </div>

                <div class="mt-6 pt-4 border-t border-slate-100 text-right">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-slate-900 hover:bg-slate-800 px-5 py-2.5 text-xs font-bold text-white transition shadow-sm">
                        <i class="fa fa-plus text-[10px] text-amber-400"></i>
                        <span>Create Account</span>
                    </button>
                </div>
            </form>

            <!-- Transfer Funds Form -->
            <form method="POST" action="{{ route('finance.transfers.store') }}" class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                @csrf
                <div class="border-b border-slate-100 pb-3 mb-4">
                    <h2 class="text-base font-extrabold text-slate-900">Transfer Between Accounts</h2>
                    <p class="text-xs text-slate-500">Move funds internally between registered accounts.</p>
                </div>

                <div class="grid gap-3.5 sm:grid-cols-2">
                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">From Account</label>
                        <div class="relative">
                            <select name="from_account_id" required class="w-full appearance-none text-xs px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                                <option value="">Select source</option>
                                @foreach($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">To Account</label>
                        <div class="relative">
                            <select name="to_account_id" required class="w-full appearance-none text-xs px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                                <option value="">Select destination</option>
                                @foreach($accounts as $a)
                                    <option value="{{ $a->id }}">{{ $a->name }}</option>
                                @endforeach
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Amount (UGX)</label>
                        <input name="amount" type="number" min=".01" step=".01" required placeholder="0.00" class="w-full text-xs px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-mono font-bold text-slate-800 transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Transfer Date</label>
                        <input name="transfer_date" type="date" value="{{ now()->toDateString() }}" required class="w-full text-xs px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Reference</label>
                        <input name="reference" placeholder="Ref/Cheque No." class="w-full text-xs px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                    </div>

                    <div>
                        <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Notes</label>
                        <input name="notes" placeholder="Reason for transfer" class="w-full text-xs px-3.5 py-2 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                    </div>
                </div>

                <div class="mt-4 pt-4 border-t border-slate-100 text-right">
                    <button type="submit" class="inline-flex items-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 px-5 py-2.5 text-xs font-extrabold uppercase tracking-wider text-slate-950 transition shadow-sm">
                        <span>Submit for Approval</span>
                    </button>
                </div>
            </form>

        </section>

        <!-- PENDING TRANSFERS TABLE -->
        @if($transfers->isNotEmpty())
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5 bg-slate-50/50">
                    <h2 class="text-base font-extrabold text-slate-900">Pending Transfers</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-3.5">Transfer Route</th>
                                <th class="px-6 py-3.5">Amount</th>
                                <th class="px-6 py-3.5">Status</th>
                                <th class="px-6 py-3.5 text-right">Control</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @foreach($transfers as $transfer)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-3.5">
                                        <span class="font-bold text-slate-900">{{ $transfer->fromAccount?->name }}</span>
                                        <span class="text-slate-400 mx-1.5">&rarr;</span>
                                        <span class="font-bold text-slate-900">{{ $transfer->toAccount?->name }}</span>
                                    </td>
                                    <td class="px-6 py-3.5 font-mono font-bold text-slate-900">UGX {{ number_format($transfer->amount,2) }}</td>
                                    <td class="px-6 py-3.5">
                                        <span class="inline-flex items-center rounded-md bg-amber-50 px-2 py-1 text-[11px] font-bold text-amber-700 border border-amber-200">
                                            {{ ucfirst($transfer->status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-3.5 text-right">
                                        @if($transfer->status==='pending')
                                            <form method="POST" action="{{ route('finance.transfers.approve',$transfer) }}" class="inline-block">
                                                @csrf
                                                <button class="inline-flex items-center gap-1.5 rounded-xl border border-emerald-300 bg-emerald-50 px-3 py-1.5 text-xs font-bold text-emerald-800 hover:bg-emerald-100 transition">
                                                    <i class="fa fa-check text-[10px]"></i> Approve
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @endif

        <!-- RECONCILIATION FORM CARD -->
        <form method="POST" action="{{ route('accounting.reconciliations.store') }}" class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
            @csrf
            <div class="border-b border-slate-100 pb-4 mb-5">
                <h2 class="text-base font-extrabold text-slate-900">Reconcile Account Balance</h2>
                <p class="text-xs text-slate-500 mt-0.5">Compares the statement balance against posted ledger credits minus posted debits up to the selected date.</p>
            </div>

            <div class="grid gap-4 md:grid-cols-4 lg:grid-cols-5 items-end">
                <div class="md:col-span-1">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Account</label>
                    <div class="relative">
                        <select name="financial_account_id" required class="w-full appearance-none text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-bold text-slate-800 transition">
                            @foreach($accounts as $account)
                                <option value="{{ $account->id }}">{{ $account->name }} ({{ ucfirst(str_replace('_',' ',$account->type)) }})</option>
                            @endforeach
                        </select>
                        <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Ending Date</label>
                    <input type="date" name="period_ending" value="{{ old('period_ending') }}" required class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                </div>

                <div>
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Statement Bal (UGX)</label>
                    <input type="number" step="0.01" name="statement_balance" value="{{ old('statement_balance') }}" placeholder="0.00" required class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-mono font-bold text-slate-800 transition">
                </div>

                <div class="md:col-span-1 lg:col-span-1">
                    <label class="block text-[11px] font-bold uppercase tracking-wider text-slate-500 mb-1">Notes</label>
                    <input name="notes" value="{{ old('notes') }}" placeholder="Reason for variance" class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-800 transition">
                </div>

                <div class="md:col-span-4 lg:col-span-1">
                    <button type="submit" class="w-full rounded-xl bg-slate-900 hover:bg-slate-800 px-4 py-2.5 text-xs font-bold text-white transition shadow-sm">
                        Save Reconciliation
                    </button>
                </div>
            </div>
        </form>

        <!-- LATEST RECONCILIATION SUMMARY & HISTORY -->
        @if($reconciliations->isNotEmpty())
            @php($latest = $reconciliations->first())
            <section class="grid gap-4 md:grid-cols-3">
                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Statement Balance</span>
                    <p class="mt-2 font-mono text-2xl font-extrabold text-slate-900">
                        <span class="text-xs font-bold text-slate-400 font-sans mr-0.5">UGX</span>{{ number_format($latest->statement_balance, 2) }}
                    </p>
                </div>

                <div class="rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider text-slate-400">Edlink Ledger Balance</span>
                    <p class="mt-2 font-mono text-2xl font-extrabold text-slate-900">
                        <span class="text-xs font-bold text-slate-400 font-sans mr-0.5">UGX</span>{{ number_format($latest->ledger_balance, 2) }}
                    </p>
                </div>

                <div class="rounded-3xl border {{ (float) $latest->difference === 0.0 ? 'border-emerald-200 bg-emerald-50/70' : 'border-rose-200 bg-rose-50/70' }} p-5 shadow-sm">
                    <span class="text-[11px] font-extrabold uppercase tracking-wider {{ (float) $latest->difference === 0.0 ? 'text-emerald-700' : 'text-rose-700' }}">Difference</span>
                    <p class="mt-2 font-mono text-2xl font-extrabold {{ (float) $latest->difference === 0.0 ? 'text-emerald-900' : 'text-rose-900' }}">
                        <span class="text-xs font-bold font-sans mr-0.5">UGX</span>{{ number_format($latest->difference, 2) }}
                    </p>
                    <p class="mt-1 text-xs font-medium {{ (float) $latest->difference === 0.0 ? 'text-emerald-700' : 'text-rose-700' }}">
                        {{ (float) $latest->difference === 0.0 ? '✓ Balances agree perfectly.' : '⚠ Investigate missing or delayed entries.' }}
                    </p>
                </div>
            </section>

            @if((float) $latest->ledger_balance === 0.0 && (float) $latest->statement_balance !== 0.0)
                <div class="flex items-center gap-2 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-xs sm:text-sm font-medium text-amber-900 shadow-sm">
                    <svg class="h-5 w-5 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                    <span><strong>No posted ledger balance was found by {{ $latest->period_ending->format('d M Y') }}.</strong> Confirm fee payments, expenses, and payroll transactions have posted entries.</span>
                </div>
            @endif

            <!-- Reconciliation History Table -->
            <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="border-b border-slate-100 p-5 bg-slate-50/50">
                    <h2 class="text-base font-extrabold text-slate-900">Reconciliation history</h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-xs text-left">
                        <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                            <tr>
                                <th class="px-6 py-3.5">Period Ending</th>
                                <th class="px-6 py-3.5">Statement</th>
                                <th class="px-6 py-3.5">Ledger</th>
                                <th class="px-6 py-3.5">Difference</th>
                                <th class="px-6 py-3.5">Notes</th>
                                <th class="px-6 py-3.5">Status & Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-slate-700 font-medium">
                            @foreach($reconciliations as $reconciliation)
                                <tr class="hover:bg-slate-50/80 transition">
                                    <td class="px-6 py-3.5 font-bold text-slate-900 whitespace-nowrap">{{ $reconciliation->period_ending->format('d M Y') }}</td>
                                    <td class="px-6 py-3.5 font-mono">UGX {{ number_format($reconciliation->statement_balance, 2) }}</td>
                                    <td class="px-6 py-3.5 font-mono">UGX {{ number_format($reconciliation->ledger_balance, 2) }}</td>
                                    <td class="px-6 py-3.5 font-mono font-bold {{ (float) $reconciliation->difference === 0.0 ? 'text-emerald-700' : 'text-rose-700' }}">
                                        UGX {{ number_format($reconciliation->difference, 2) }}
                                    </td>
                                    <td class="px-6 py-3.5 text-slate-500 max-w-xs truncate">{{ $reconciliation->notes ?: '—' }}</td>
                                    <td class="px-6 py-3.5 whitespace-nowrap">
                                        <div class="text-slate-900 font-semibold">{{ $reconciliation->account?->name }}</div>
                                        <div class="text-[11px] text-slate-400 capitalize">{{ $reconciliation->reconciled_at?->format('d M Y H:i') ?: 'Unclosed' }} · {{ $reconciliation->status }}</div>
                                        @if($reconciliation->status === 'closed')
                                            <form method="POST" action="{{ route('accounting.reconciliations.reopen',$reconciliation) }}" class="mt-2 flex items-center gap-1.5">
                                                @csrf
                                                <input name="reason" required minlength="8" placeholder="Reopen reason" class="text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-amber-500 font-medium">
                                                <button class="rounded-lg bg-amber-100 hover:bg-amber-200 px-2.5 py-1 text-[11px] font-bold text-amber-900 transition">Reopen</button>
                                            </form>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </section>
        @else
            <div class="rounded-3xl border border-dashed border-slate-300 bg-slate-50/50 p-8 text-center text-xs font-semibold text-slate-400">
                No reconciliation records found for this account.
            </div>
        @endif

        <!-- MAIN LEDGER ENTRIES LISTING -->
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <div class="border-b border-slate-100 p-5 bg-slate-50/50">
                <h2 class="text-base font-extrabold text-slate-900">Ledger Transactions</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-50 border-b border-slate-100 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                        <tr>
                            <th class="px-6 py-3.5">Reference</th>
                            <th class="px-6 py-3.5">Type</th>
                            <th class="px-6 py-3.5">Direction</th>
                            <th class="px-6 py-3.5">Amount (UGX)</th>
                            <th class="px-6 py-3.5">Status</th>
                            <th class="px-6 py-3.5 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                        @forelse($entries as $entry)
                            <tr class="hover:bg-slate-50/80 transition">
                                <td class="px-6 py-3.5 font-mono font-bold text-slate-900">{{ $entry->reference }}</td>
                                <td class="px-6 py-3.5 capitalize">{{ $entry->entry_type }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-extrabold uppercase {{ $entry->direction === 'credit' ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-slate-100 text-slate-700 border border-slate-200' }}">
                                        {{ $entry->direction }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 font-mono font-bold text-slate-900">{{ number_format($entry->amount, 2) }}</td>
                                <td class="px-6 py-3.5">
                                    <span class="inline-flex items-center rounded-md px-2 py-0.5 text-[10px] font-bold uppercase {{ $entry->status === 'posted' ? 'bg-emerald-50 text-emerald-700' : ($entry->status === 'pending' ? 'bg-amber-50 text-amber-700' : 'bg-rose-50 text-rose-700') }}">
                                        {{ ucfirst($entry->status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-3.5 text-right whitespace-nowrap">
                                    @if($entry->status === 'pending')
                                        <form class="inline-block mr-1" method="POST" action="{{ route('finance.ledger.approve',$entry) }}">
                                            @csrf
                                            <button class="rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 px-2.5 py-1 text-[11px] font-bold text-emerald-800 transition">Approve</button>
                                        </form>
                                        <form class="inline-flex items-center gap-1" method="POST" action="{{ route('finance.ledger.reject',$entry) }}">
                                            @csrf
                                            <input name="reason" required minlength="8" placeholder="Rejection reason" class="text-xs px-2 py-1 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-rose-500 font-medium">
                                            <button class="rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 px-2 py-1 text-[11px] font-bold text-rose-800 transition">Reject</button>
                                        </form>
                                    @endif

                                    @if($entry->status === 'posted' && !$entry->reversal_of_id)
                                        <form class="inline-flex items-center gap-1" method="POST" action="{{ route('finance.ledger.reverse',$entry) }}">
                                            @csrf
                                            <input name="reason" required minlength="8" placeholder="Reversal reason" class="text-xs px-2.5 py-1 bg-slate-50 border border-slate-200 rounded-lg focus:outline-none focus:border-rose-500 font-medium">
                                            <button class="rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 px-2.5 py-1 text-[11px] font-bold text-rose-800 transition">Reverse</button>
                                        </form>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="p-12 text-center text-slate-400 italic">
                                    No ledger entries recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- PAGINATION -->
            <div class="border-t border-slate-100 bg-slate-50/50 px-6 py-3.5">
                {{ $entries->links() }}
            </div>
        </section>

    </div>
</x-app-layout>
