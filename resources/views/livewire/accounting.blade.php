<div class="space-y-6">
    <header class="overflow-hidden rounded-3xl bg-slate-900 shadow-lg ring-1 ring-slate-800">
        <div class="relative px-6 py-7 sm:px-8">
            <div class="pointer-events-none absolute -right-16 -top-20 h-56 w-56 rounded-full bg-amber-400/10"></div>
            <div class="pointer-events-none absolute right-20 top-16 h-24 w-24 rounded-full bg-white/5"></div>
            <div class="relative flex flex-col gap-6 xl:flex-row xl:items-center xl:justify-between">
                <div class="flex items-start gap-4">

                    <div>
                        <div class="flex flex-wrap items-center gap-3">
                            <h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">Accounting Workspace</h1>
                            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/20 bg-emerald-400/10 px-3 py-1 text-[11px] font-bold uppercase tracking-wider text-emerald-300">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> Double-entry enabled
                            </span>
                        </div>
                        <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-300">Manage the chart of accounts, controlled journals, reporting periods and financial statements for {{ auth()->user()->school?->name }}.</p>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 sm:flex">
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Base currency</p>
                        <p class="mt-1 font-black text-white">{{ $currency }}</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                        <p class="text-[10px] font-bold uppercase tracking-widest text-slate-400">Ledger status</p>
                        <p class="mt-1 font-black {{ abs($summary['debits']-$summary['credits']) < .005 ? 'text-emerald-300' : 'text-red-300' }}">{{ abs($summary['debits']-$summary['credits']) < .005 ? 'Balanced' : 'Review needed' }}</p>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex gap-1 overflow-x-auto border-t border-white/10 bg-slate-950/30 px-4 py-2 sm:px-6">
            @foreach(['dashboard'=>'Dashboard','accounts'=>'Chart of accounts','journals'=>'Journals','reports'=>'Reports','settings'=>'Posting rules','periods'=>'Periods'] as $key=>$label)
                <button wire:click="setTab('{{ $key }}')" class="whitespace-nowrap rounded-xl px-4 py-2.5 text-sm font-bold transition {{ $tab===$key?'bg-amber-400 text-slate-950 shadow-sm':'text-slate-300 hover:bg-white/10 hover:text-white' }}">{{ $label }}</button>
            @endforeach
        </div>
    </header>

    <div class="relative max-w-xl">
        <svg class="pointer-events-none absolute left-4 top-1/2 h-4 w-4 -translate-y-1/2 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m21 21-4.35-4.35M19 11a8 8 0 1 1-16 0 8 8 0 0 1 16 0Z"/></svg>
        <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search account code, account name, journal, reference or status..." class="w-full rounded-2xl border border-slate-200 bg-white py-3 pl-11 pr-4 text-sm shadow-sm transition focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/20">
    </div>

    @if(session('status'))<div class="rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800">{{ session('status') }}</div>@endif
    @if($errors->any())<div class="rounded-2xl border border-red-200 bg-red-50 p-4 text-sm text-red-800"><ul class="list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif

    @if($tab==='dashboard')
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            @foreach([['Cash & assets',$summary['assets'],'text-blue-700'],['Receivables',$receivables->sum('balance'),'text-violet-700'],['Income',$summary['income'],'text-emerald-700'],['Current surplus / deficit',$summary['surplus'],$summary['surplus']>=0?'text-emerald-700':'text-red-700']] as [$label,$value,$tone])
                <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><p class="text-sm font-semibold text-slate-500">{{ $label }}</p><p class="mt-2 text-2xl font-black {{ $tone }}">{{ $currency }} {{ number_format($value,2) }}</p></div>
            @endforeach
        </div>
        <div class="flex flex-wrap gap-3"><button wire:click="generateFeeAssessments" wire:confirm="Generate controlled tuition assessments for all active enrolments in the current term?" class="rounded-xl bg-yellow-500 px-4 py-3 text-sm font-black text-slate-950">Generate current-term assessments</button><button wire:click="setTab('journals')" class="rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm font-black text-slate-800">Review journal approvals</button></div>
        <div class="grid gap-6 xl:grid-cols-3">
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200 xl:col-span-2"><div class="flex justify-between"><h2 class="font-black text-slate-900">Accounting health</h2><span class="rounded-full px-3 py-1 text-xs font-bold {{ abs($summary['debits']-$summary['credits'])<.005?'bg-emerald-100 text-emerald-800':'bg-red-100 text-red-800' }}">Debits {{ abs($summary['debits']-$summary['credits'])<.005?'balance':'do not balance' }}</span></div><div class="mt-5 grid gap-4 sm:grid-cols-3"><div><p class="text-xs uppercase text-slate-500">Total debits</p><p class="font-black">{{ $currency }} {{ number_format($summary['debits'],2) }}</p></div><div><p class="text-xs uppercase text-slate-500">Total credits</p><p class="font-black">{{ $currency }} {{ number_format($summary['credits'],2) }}</p></div><div><p class="text-xs uppercase text-slate-500">Expenses</p><p class="font-black">{{ $currency }} {{ number_format($summary['expenses'],2) }}</p></div></div></div>
            <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="font-black">Exceptions</h2><div class="mt-4 space-y-3 text-sm"><p class="flex justify-between"><span>Unapproved journals</span><b>{{ $journals->getCollection()->whereIn('status',['draft','submitted','approved'])->count() }}</b></p><p class="flex justify-between"><span>Unmapped money accounts</span><b>{{ $financialAccounts->whereNull('ledger_account_id')->count() }}</b></p><p class="flex justify-between"><span>Locked periods</span><b>{{ $periods->where('status','locked')->count() }}</b></p></div></div>
        </div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="font-black">Recently posted</h2><div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Date</th><th>Journal</th><th>Description</th><th>Type</th></tr></thead><tbody>@forelse($recent as $journal)<tr class="border-t"><td class="py-3">{{ $journal->journal_date->format('d M Y') }}</td><td class="font-bold">{{ $journal->number }}</td><td>{{ $journal->description }}</td><td>{{ str($journal->journal_type)->headline() }}</td></tr>@empty<tr><td colspan="4" class="py-8 text-center text-slate-500">No posted journals yet.</td></tr>@endforelse</tbody></table></div></div>
    @endif

    @if($tab==='accounts')
        <section class="grid gap-6 xl:grid-cols-[340px_minmax(0,1fr)]">
            <form id="account-editor" wire:submit="{{ $editingAccountId ? 'saveAccount' : 'addAccount' }}" class="h-fit space-y-4 rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                <div class="border-b border-slate-100 pb-4">
                    <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-600">Account setup</p>
                    <h2 class="mt-1 text-lg font-black text-slate-900">{{ $editingAccountId ? 'Edit ledger account' : 'Add ledger account' }}</h2>
                    <p class="mt-1 text-xs leading-5 text-slate-500">Create a posting account or a parent heading for your chart.</p>
                </div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-500">Account to edit
                    <select wire:change="editAccount($event.target.value)" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm font-semibold text-slate-800 transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30">
                        <option value="">New account</option>
                        @foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach
                    </select>
                </label>
                <div class="grid grid-cols-[110px_1fr] gap-3">
                    <label class="text-xs font-bold text-slate-600">Code<input wire:model="accountCode" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 font-mono text-sm font-bold transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30" placeholder="5800" /></label>
                    <label class="text-xs font-bold text-slate-600">Account name<input wire:model="accountName" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30" placeholder="Account name" /></label>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <label class="text-xs font-bold text-slate-600">Class<select wire:model.live="accountClass" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30">@foreach(['asset','liability','equity','income','expense'] as $class)<option value="{{ $class }}">{{ ucfirst($class) }}</option>@endforeach</select></label>
                    <label class="text-xs font-bold text-slate-600">Normal balance<select wire:model="normalBalance" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30"><option>debit</option><option>credit</option></select></label>
                </div>
                <label class="block text-xs font-bold text-slate-600">Subtype<input wire:model="accountSubtype" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30" placeholder="e.g. utilities" /></label>
                <label class="block text-xs font-bold text-slate-600">Parent account<select wire:model="parentId" class="mt-1.5 w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30"><option value="">No parent (top level)</option>@foreach($accounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></label>
                <label class="flex items-center gap-3 rounded-xl bg-slate-50 p-3 text-sm font-semibold text-slate-700"><input type="checkbox" wire:model="acceptsPostings" class="rounded border-slate-300 text-amber-500 focus:ring-amber-500"> Accept direct postings</label>
                <div class="flex gap-2 border-t border-slate-100 pt-4">
                    <button class="flex-1 rounded-xl bg-slate-900 px-4 py-3 text-sm font-black text-white hover:bg-slate-800">{{ $editingAccountId ? 'Save changes' : 'Add account' }}</button>
                    @if($editingAccountId)<button type="button" wire:click="cancelAccountEdit" class="rounded-xl border border-slate-200 px-4 py-3 text-sm font-bold text-slate-600">Cancel</button>@endif
                </div>
                @if($editingAccountId)
                    <div class="rounded-2xl border border-red-100 bg-red-50 p-3">
                        @if($editingAccountIsSystem)
                            <p class="text-xs font-semibold leading-5 text-red-700">This is a protected system ledger. It can be edited, but it cannot be deleted.</p>
                        @else
                            <button type="button" wire:click="deleteAccount" wire:confirm="Delete this ledger account permanently? This is only allowed when the account has no transactions, subaccounts, mappings or other links." class="w-full rounded-xl border border-red-200 bg-white px-4 py-2.5 text-sm font-black text-red-700 hover:bg-red-100">Delete ledger account</button>
                            <p class="mt-2 text-[10px] leading-4 text-red-600">Deletion is permanent and only works for completely unused accounts. Use Archive when history must be retained.</p>
                        @endif
                    </div>
                @endif
            </form>

            <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
                <div class="flex flex-col gap-3 border-b border-slate-100 bg-slate-50/60 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <div><h2 class="text-lg font-black text-slate-900">Chart of accounts</h2><p class="mt-1 text-xs text-slate-500">{{ $accounts->count() }} accounts organised by code and reporting class. @if(auth()->user()->hasPermission('accounting.accounts.manage'))Tap any row to edit it.@endif</p></div>
                    <div class="flex gap-2 text-[10px] font-bold uppercase tracking-wider"><span class="rounded-full bg-white px-3 py-1.5 text-slate-600 ring-1 ring-slate-200">{{ $accounts->where('accepts_postings', true)->count() }} posting</span><span class="rounded-full bg-violet-50 px-3 py-1.5 text-violet-700 ring-1 ring-violet-100">{{ $accounts->where('is_control_account', true)->count() }} control</span></div>
                </div>
                @php($accountsByParent = $accounts->groupBy(fn ($account) => $account->parent_id ?: 0))
                <div x-data="{ accountClass: 'asset' }">
                    <div class="flex gap-2 overflow-x-auto border-b border-slate-100 px-4 py-3 sm:px-6">
                        @foreach(['asset' => 'Assets', 'liability' => 'Liabilities', 'equity' => 'Equity & funds', 'income' => 'Income', 'expense' => 'Expenses'] as $classKey => $classLabel)
                            <button type="button" x-on:click="accountClass = '{{ $classKey }}'" :class="accountClass === '{{ $classKey }}' ? 'bg-slate-900 text-white shadow-sm' : 'bg-slate-100 text-slate-600 hover:bg-slate-200'" class="whitespace-nowrap rounded-xl px-4 py-2.5 text-xs font-black transition">
                                {{ $classLabel }} <span class="ml-1 opacity-60">{{ $accounts->where('account_class', $classKey)->count() }}</span>
                            </button>
                        @endforeach
                    </div>
                    <div class="grid min-w-[760px] grid-cols-[minmax(330px,1fr)_110px_120px_180px] border-b border-slate-100 bg-slate-50 px-5 py-3 text-[10px] font-black uppercase tracking-widest text-slate-400">
                        <span>Account hierarchy</span><span>Normal</span><span>Usage</span><span class="text-right">Status & action</span>
                    </div>
                    @foreach(['asset', 'liability', 'equity', 'income', 'expense'] as $classKey)
                        <div x-show="accountClass === '{{ $classKey }}'" x-cloak class="min-w-[760px] divide-y divide-slate-100">
                            @foreach($accountsByParent->get(0, collect())->where('account_class', $classKey) as $account)
                                @include('livewire.partials.account-tree-row', ['account' => $account, 'accountsByParent' => $accountsByParent, 'depth' => 0])
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if($tab==='journals')
        <form wire:submit="saveJournal" class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm ring-4 ring-yellow-400/10">
            <div class="flex flex-col gap-4 border-b border-gray-100 bg-slate-50/50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                <div><p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-600">Ledger transaction</p><h2 class="mt-1 text-lg font-black text-darken">Manual journal entry</h2><p class="mt-1 text-xs text-gray-400">Enter at least two lines. Total debits and credits must be exactly equal.</p></div>
                <button type="button" wire:click="addJournalLine" class="inline-flex items-center justify-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-xs font-bold text-darken shadow-sm transition-all hover:border-yellow-400 hover:bg-yellow-50 focus:outline-none focus:ring-4 focus:ring-yellow-400/30"><span class="text-lg leading-none text-amber-500">+</span> Add transaction line</button>
            </div>

            <div class="space-y-6 p-6 lg:p-8">
                <div class="grid gap-5 md:grid-cols-3">
                    <div><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Journal date</label><input type="date" wire:model="journalDate" class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30"></div>
                    <div><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Reference <span class="normal-case tracking-normal text-gray-300">(optional)</span></label><input wire:model="journalReference" placeholder="e.g. JV-001 or invoice number" class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30"></div>
                    <div><label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Transaction narration</label><input wire:model="journalDescription" placeholder="Briefly explain this transaction" class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30"></div>
                </div>

                <div class="space-y-3">
                    <div class="hidden grid-cols-[minmax(240px,1.2fr)_minmax(190px,1fr)_150px_150px_44px] gap-3 px-3 text-[10px] font-black uppercase tracking-widest text-gray-400 lg:grid"><span>Ledger account</span><span>Line description</span><span>Debit ({{ $currency }})</span><span>Credit ({{ $currency }})</span><span></span></div>
                    @foreach($journalLines as $index=>$line)
                        <div wire:key="journal-line-{{ $index }}" class="grid gap-3 rounded-2xl border border-gray-100 bg-gray-50/50 p-4 lg:grid-cols-[minmax(240px,1.2fr)_minmax(190px,1fr)_150px_150px_44px] lg:items-end">
                            <div><label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500 lg:hidden">Ledger account</label><select wire:model="journalLines.{{ $index }}.ledger_account_id" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/30"><option value="">Select posting account</option>@foreach($postingAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach</select></div>
                            <div><label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500 lg:hidden">Line description</label><input wire:model="journalLines.{{ $index }}.description" placeholder="What is this line for?" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/30"></div>
                            <div><label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500 lg:hidden">Debit ({{ $currency }})</label><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs font-bold text-gray-300">Dr</span><input type="number" step="0.01" min="0" wire:model="journalLines.{{ $index }}.debit" placeholder="0.00" class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-right font-mono text-sm font-bold transition-all focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/30"></div></div>
                            <div><label class="mb-1.5 block text-[10px] font-bold uppercase tracking-wider text-gray-500 lg:hidden">Credit ({{ $currency }})</label><div class="relative"><span class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-xs font-bold text-gray-300">Cr</span><input type="number" step="0.01" min="0" wire:model="journalLines.{{ $index }}.credit" placeholder="0.00" class="w-full rounded-xl border border-gray-200 bg-white py-2.5 pl-10 pr-3 text-right font-mono text-sm font-bold transition-all focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/30"></div></div>
                            <button type="button" wire:click="removeJournalLine({{ $index }})" title="Remove line" class="flex h-10 w-10 items-center justify-center rounded-xl border border-red-100 bg-white text-lg font-bold text-red-500 transition hover:bg-red-50 disabled:cursor-not-allowed disabled:opacity-30" {{ count($journalLines) <= 2 ? 'disabled' : '' }}>×</button>
                        </div>
                    @endforeach
                </div>

                <div class="flex flex-col gap-4 border-t border-gray-100 pt-5 sm:flex-row sm:items-center sm:justify-between">
                    <div class="rounded-xl border border-dashed border-gray-200 bg-gray-50/50 px-4 py-2.5 text-xs text-gray-500"><span class="font-bold text-darken">Control:</span> The journal is saved as a draft and must be approved by another authorized user.</div>
                    <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-yellow-400 px-7 py-3 text-sm font-black text-darken shadow-sm transition-all hover:bg-yellow-500 hover:shadow focus:outline-none focus:ring-4 focus:ring-yellow-400/30"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>Save balanced draft</button>
                </div>
            </div>
        </form>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="flex flex-wrap items-end gap-3"><div><h2 class="font-black">Journal register</h2><p class="text-sm text-slate-500">Posted journals are immutable. Corrections use reversals.</p></div><label class="ml-auto text-xs font-bold">Rejection / reversal reason<input wire:model="actionReason" class="mt-1 rounded-xl border-slate-300 text-sm" placeholder="Required for reversal"></label></div><div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Number</th><th>Date</th><th>Narration</th><th>Status</th><th>Debit</th><th>Actions</th></tr></thead><tbody>@foreach($journals as $journal)<tr class="border-t"><td class="py-3 font-bold">{{ $journal->number }}</td><td>{{ $journal->journal_date->format('d M Y') }}</td><td>{{ $journal->description }}</td><td><span class="rounded-full bg-slate-100 px-2 py-1 text-xs font-bold">{{ ucfirst($journal->status) }}</span></td><td>{{ $currency }} {{ number_format($journal->lines->sum('debit'),2) }}</td><td><div class="flex gap-2 text-xs font-bold">@if($journal->status==='draft')<button wire:click="submitJournal({{ $journal->id }})">Submit</button>@elseif($journal->status==='submitted')<button wire:click="approveJournal({{ $journal->id }})">Approve</button>@elseif($journal->status==='approved')<button wire:click="postJournal({{ $journal->id }})">Post</button>@elseif($journal->status==='posted')<button class="text-red-700" wire:click="reverseJournal({{ $journal->id }})">Reverse</button>@endif</div></td></tr>@endforeach</tbody></table></div><div class="mt-4">{{ $journals->links() }}</div></div>
    @endif

    @if($tab==='reports')
        @php($auditReports = ['trial-balance'=>'Trial Balance','general-ledger'=>'General Ledger','journal-register'=>'Journal Register','income-expenditure'=>'Income & Expenditure','financial-position'=>'Financial Position','cashbook'=>'Cashbook','receivables-aging'=>'Student Receivables','expense-analysis'=>'Expense Analysis','chart-of-accounts'=>'Chart of Accounts','audit-trail'=>'Audit Trail'])
        <div class="rounded-2xl bg-slate-900 p-5 text-white shadow-sm"><div class="flex flex-wrap items-end gap-3"><div><p class="text-[10px] font-black uppercase tracking-[.2em] text-yellow-400">Audit pack</p><h2 class="mt-1 text-lg font-black">Print-ready reports and Excel workbooks</h2><p class="mt-1 text-xs text-slate-300">Every export is tenant-scoped and carries the selected reporting period.</p></div><div class="ml-auto flex gap-2"><label class="text-[10px] font-bold uppercase text-slate-300">From<input type="date" wire:model.live="from" class="mt-1 block rounded-xl border-slate-600 bg-slate-800 text-xs text-white"></label><label class="text-[10px] font-bold uppercase text-slate-300">To<input type="date" wire:model.live="to" class="mt-1 block rounded-xl border-slate-600 bg-slate-800 text-xs text-white"></label></div></div></div>
        <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">@foreach($auditReports as $key=>$label)<div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm"><h3 class="text-sm font-black text-slate-900">{{ $label }}</h3><p class="mt-1 text-[11px] text-slate-500">Auditor-ready detail and control evidence.</p><div class="mt-4 flex gap-2"><a href="{{ route('accounting.exports',['report'=>$key,'format'=>'pdf','from'=>$from,'to'=>$to]) }}" class="rounded-lg bg-red-50 px-3 py-2 text-[10px] font-black uppercase text-red-700">PDF</a><a href="{{ route('accounting.exports',['report'=>$key,'format'=>'xlsx','from'=>$from,'to'=>$to]) }}" class="rounded-lg bg-emerald-50 px-3 py-2 text-[10px] font-black uppercase text-emerald-700">Excel</a></div></div>@endforeach</div>
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="flex flex-wrap items-end gap-3"><div><h2 class="font-black">Trial balance</h2><p class="text-sm text-slate-500">Only posted entries are included.</p></div><label class="ml-auto text-xs font-bold">From<input type="date" wire:model.live="from" class="mt-1 block rounded-xl border-slate-300"></label><label class="text-xs font-bold">To<input type="date" wire:model.live="to" class="mt-1 block rounded-xl border-slate-300"></label></div><div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Code</th><th>Account</th><th class="text-right">Debit</th><th class="text-right">Credit</th></tr></thead><tbody>@foreach($trialBalance as $row)@if((float)$row->debit || (float)$row->credit)<tr class="border-t"><td class="py-3 font-mono">{{ $row->code }}</td><td>{{ $row->name }}</td><td class="text-right">{{ number_format($row->debit,2) }}</td><td class="text-right">{{ number_format($row->credit,2) }}</td></tr>@endif @endforeach</tbody><tfoot class="border-t-2 font-black"><tr><td colspan="2" class="py-3">Totals</td><td class="text-right">{{ number_format($summary['debits'],2) }}</td><td class="text-right">{{ number_format($summary['credits'],2) }}</td></tr></tfoot></table></div></div>
        <div class="grid gap-6 lg:grid-cols-2"><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="font-black">Income and expenditure</h2><div class="mt-4 space-y-3 text-sm"><p class="flex justify-between"><span>Income</span><b>{{ $currency }} {{ number_format($summary['income'],2) }}</b></p><p class="flex justify-between"><span>Expenses</span><b>({{ $currency }} {{ number_format($summary['expenses'],2) }})</b></p><p class="flex justify-between border-t pt-3 text-base"><span>Surplus / deficit</span><b>{{ $currency }} {{ number_format($summary['surplus'],2) }}</b></p></div></div><div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><h2 class="font-black">Statement of financial position</h2><div class="mt-4 space-y-3 text-sm"><p class="flex justify-between"><span>Assets</span><b>{{ $currency }} {{ number_format($summary['assets'],2) }}</b></p><p class="flex justify-between"><span>Liabilities</span><b>{{ $currency }} {{ number_format($summary['liabilities'],2) }}</b></p><p class="flex justify-between"><span>Funds and accumulated result</span><b>{{ $currency }} {{ number_format($summary['equity']+$summary['surplus'],2) }}</b></p></div></div></div>
    @endif

    @if($tab==='settings')
        @php($mappingGroups = [
            'Fees & receivables' => ['student_receivable', 'default_fee_income', 'fees_received_in_advance', 'fee_discount', 'scholarship', 'bad_debt'],
            'Expenses & payroll' => ['default_expense', 'supplier_payable', 'teaching_salary_expense', 'non_teaching_salary_expense', 'staff_benefits_expense', 'salaries_payable', 'statutory_deductions_payable'],
            'Balances & controls' => ['bank_charges', 'rounding_differences', 'opening_balance', 'retained_surplus'],
        ])
        <form wire:submit="saveMappings" class="space-y-6">
            <section class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm ring-4 ring-yellow-400/10">
                <div class="border-b border-gray-100 bg-slate-50/50 px-6 py-5">
                    <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-600">Cash integration</p>
                    <h2 class="mt-1 text-lg font-black text-darken">Operational money accounts</h2>
                    <p class="mt-1 text-xs text-gray-400">Connect every cash, bank and mobile-money account to a unique general-ledger account.</p>
                </div>
                <div class="grid gap-5 p-6 md:grid-cols-2">
                    @foreach($financialAccounts as $financial)
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/50 p-4">
                            <div class="mb-3 flex items-center justify-between gap-3">
                                <div><p class="font-bold text-darken">{{ $financial->name }}</p><p class="text-[10px] font-semibold uppercase tracking-wider text-gray-400">{{ str($financial->type)->headline() }}</p></div>
                                <span class="rounded-full bg-white px-3 py-1 text-[10px] font-black text-slate-600 ring-1 ring-gray-200">{{ $financial->currency }}</span>
                            </div>
                            <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">Mapped ledger account</label>
                            <select wire:model="financialMappings.{{ $financial->id }}" class="w-full rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium transition-all focus:border-yellow-400 focus:outline-none focus:ring-4 focus:ring-yellow-400/30">
                                <option value="">Select an asset account</option>
                                @foreach($postingAccounts->where('account_class','asset')->where('currency',$financial->currency) as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach
                            </select>
                            @error("financialMappings.{$financial->id}")<span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>@enderror
                        </div>
                    @endforeach
                </div>
            </section>

            <section class="overflow-hidden rounded-3xl border border-gray-100 bg-white shadow-sm">
                <div class="border-b border-gray-100 bg-slate-50/50 px-6 py-5">
                    <p class="text-[10px] font-black uppercase tracking-[.2em] text-amber-600">Automation setup</p>
                    <h2 class="mt-1 text-lg font-black text-darken">Posting rules</h2>
                    <p class="mt-1 text-xs text-gray-400">Choose the ledger used automatically when operational transactions are approved.</p>
                </div>
                <div class="space-y-7 p-6 lg:p-8">
                    @foreach($mappingGroups as $groupLabel => $mappingTypes)
                        <div>
                            <div class="mb-4 flex items-center gap-3"><span class="flex h-7 w-7 items-center justify-center rounded-lg bg-yellow-400 text-xs font-black text-darken">{{ $loop->iteration }}</span><div><h3 class="text-sm font-black text-darken">{{ $groupLabel }}</h3><p class="text-[10px] text-gray-400">Automatic debit and credit destinations</p></div><div class="h-px flex-1 bg-gray-100"></div></div>
                            <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                                @foreach($mappingTypes as $type)
                                    @continue(! array_key_exists($type, $mappings))
                                    <div>
                                        <label class="mb-1.5 block text-xs font-semibold uppercase tracking-wider text-gray-500">{{ str($type)->headline() }}</label>
                                        <select wire:model="mappings.{{ $type }}" class="w-full rounded-xl border border-gray-200 bg-gray-50/50 px-4 py-2.5 text-sm transition-all focus:border-yellow-400 focus:bg-white focus:outline-none focus:ring-4 focus:ring-yellow-400/30">
                                            <option value="">Select posting account</option>
                                            @foreach($postingAccounts as $account)<option value="{{ $account->id }}">{{ $account->code }} · {{ $account->name }}</option>@endforeach
                                        </select>
                                        @error("mappings.{$type}")<span class="mt-1.5 block text-xs font-medium text-red-500">{{ $message }}</span>@enderror
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="flex flex-col gap-3 border-t border-gray-100 bg-gray-50/50 px-6 py-5 sm:flex-row sm:items-center sm:justify-between">
                    <p class="max-w-2xl text-xs leading-5 text-gray-500"><span class="font-bold text-darken">Important:</span> New rules apply to future postings. Existing posted journals are never rewritten.</p>
                    <button class="inline-flex shrink-0 items-center justify-center gap-2 rounded-xl bg-yellow-400 px-7 py-3 text-sm font-black text-darken shadow-sm transition-all hover:bg-yellow-500 hover:shadow focus:outline-none focus:ring-4 focus:ring-yellow-400/30"><svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>Save posting rules</button>
                </div>
            </section>
        </form>
    @endif

    @if($tab==='periods')
        <div class="rounded-2xl bg-white p-5 shadow-sm ring-1 ring-slate-200"><div class="flex items-end gap-3"><div><h2 class="font-black">Accounting periods</h2><p class="text-sm text-slate-500">Soft-closed and locked periods reject all postings.</p></div><label class="ml-auto text-xs font-bold">Reopening reason<input wire:model="actionReason" class="mt-1 block rounded-xl border-slate-300 text-sm"></label></div><div class="mt-4 overflow-x-auto"><table class="min-w-full text-sm"><thead class="text-left text-xs uppercase text-slate-500"><tr><th class="py-2">Period</th><th>Dates</th><th>Status</th><th></th></tr></thead><tbody>@foreach($periods as $period)<tr class="border-t"><td class="py-3 font-bold">{{ $period->name }}</td><td>{{ $period->starts_on->format('d M') }} – {{ $period->ends_on->format('d M Y') }}</td><td>{{ str($period->status)->headline() }}</td><td class="text-right space-x-2">@if($period->status==='open')<button wire:click="changePeriodStatus({{ $period->id }},'soft_closed')" class="font-bold">Soft close</button><button wire:click="changePeriodStatus({{ $period->id }},'locked')" class="font-bold text-red-700">Lock</button>@else<button wire:click="changePeriodStatus({{ $period->id }},'open')" class="font-bold text-emerald-700">Reopen</button>@endif</td></tr>@endforeach</tbody></table></div></div>
    @endif
</div>
