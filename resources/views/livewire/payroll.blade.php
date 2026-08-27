<div class="space-y-6">
    
    <!-- Top Banner / Header -->
    <header class="relative overflow-hidden rounded-3xl bg-slate-900 p-6 sm:p-8 text-white shadow-xl">
        <div class="relative z-10 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
            <div>
                <h1 class="text-2xl sm:text-3xl font-extrabold text-amber-300 tracking-tight">
                    Staff Payroll
                </h1>
                <p class="mt-1 text-sm text-slate-400 max-w-xl">
                    Pay active staff salaries, log advance disbursements, and track remaining balances.
                </p>
            </div>
        </div>

        <!-- Ambient background glow -->
        <div class="absolute -right-16 -top-16 h-64 w-64 rounded-full bg-amber-400/10 blur-3xl pointer-events-none"></div>
    </header>

    <!-- Alert Messages -->
    @if (session('status'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/80 p-4 text-sm text-emerald-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-emerald-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('status') }}</p>
        </div>
    @endif

    @if (session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/80 p-4 text-sm text-rose-900 shadow-sm backdrop-blur-sm">
            <svg class="h-5 w-5 text-rose-600 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
            <p class="font-medium">{{ session('error') }}</p>
        </div>
    @endif

    <!-- Controls Bar -->
    <div class="flex flex-col sm:flex-row items-stretch sm:items-end gap-4 rounded-3xl border border-slate-200/80 bg-white p-5 shadow-sm">
        <div class="w-full sm:w-64">
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Payroll Period</label>
            <div class="relative">
                <input wire:model.live="period" 
                       type="month" 
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-3.5 pr-10 text-sm text-slate-800 font-medium placeholder-slate-400 transition-all duration-200 ease-in-out hover:bg-white focus:bg-white focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
            </div>
        </div>
        
        <div class="flex-1">
            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Search Staff</label>
            <div class="relative">
                <input wire:model.live="search" 
                       type="text" 
                       placeholder="Search by name, staff number, or job title..." 
                       class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 pl-10 pr-4 text-sm text-slate-800 placeholder-slate-400 transition-all duration-200 ease-in-out hover:bg-white focus:bg-white focus:border-amber-500 focus:outline-none focus:ring-4 focus:ring-amber-500/10">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Layout -->
    <div class="grid gap-8 {{ $selectedStaff ? 'lg:grid-cols-3' : '' }} items-start">
        
        <!-- Left: Staff Table -->
        <div class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm {{ $selectedStaff ? 'lg:col-span-2' : '' }}">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-extrabold text-slate-900">Staff Payroll Roster</h2>
                <p class="text-xs text-slate-500 mt-0.5">Select a staff member to process or review payment</p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Staff</th>
                            <th class="px-6 py-3.5">Title</th>
                            <th class="px-6 py-3.5">Salary</th>
                            <th class="px-6 py-3.5">Remaining ({{ $period }})</th>
                            <th class="px-6 py-3.5 text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @forelse ($staff as $member)
                            @php $memberSummary = $summaries[$member->id] ?? null; @endphp
                            <tr class="transition {{ $selectedStaffId === $member->id ? 'bg-amber-50/60' : 'hover:bg-slate-50/60' }}">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="h-9 w-9 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-700 text-xs">
                                            {{ strtoupper(substr($member->name, 0, 2)) }}
                                        </div>
                                        <p class="font-bold text-slate-900">{{ $member->name }}</p>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600">
                                    {{ $member->job_title ?? '—' }}
                                </td>
                                <td class="px-6 py-4 font-mono font-semibold text-slate-800 whitespace-nowrap">
                                    <span class="text-xs text-slate-400 font-normal">UGX</span> {{ number_format($member->base_salary) }}
                                </td>
                                <td class="px-6 py-4 font-mono whitespace-nowrap">
                                    <span class="font-semibold text-slate-800"><span class="text-xs text-slate-400 font-normal">UGX</span> {{ number_format($memberSummary['remaining'] ?? 0) }}</span>
                                    @if (($memberSummary['remaining'] ?? 0) <= 0)
                                        <span class="ml-2 inline-flex items-center gap-1 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-bold text-emerald-700 ring-1 ring-emerald-600/20">
                                            <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Fully Paid
                                        </span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right whitespace-nowrap">
                                    <button wire:click="selectStaff({{ $member->id }})" 
                                            class="rounded-xl px-4 py-2 text-xs font-bold transition shadow-sm
                                            {{ $selectedStaffId === $member->id 
                                                ? 'bg-slate-900 text-white' 
                                                : 'bg-amber-400 hover:bg-amber-500 text-slate-950' }}">
                                        {{ $selectedStaffId === $member->id ? 'Selected' : 'Pay' }}
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-400">
                                    <svg class="mx-auto h-8 w-8 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    No staff records found for this criteria.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Right: Selected Staff Payment Form Sidebar -->
        @if ($selectedStaff)
            <div class="space-y-6">
                
                <!-- Payment Processing Box -->
                <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                    <div class="flex items-start justify-between border-b border-slate-100 pb-4">
                        <div>
                            <span class="inline-block rounded-md bg-amber-100 px-2 py-0.5 text-[11px] font-bold uppercase tracking-wider text-amber-800 mb-1">
                                Selected Staff
                            </span>
                            <h2 class="text-lg font-extrabold text-slate-900">{{ $selectedStaff->name }}</h2>
                            <p class="text-xs font-medium text-slate-500">{{ $selectedStaff->job_title ?? 'Staff Member' }}</p>
                        </div>
                        <button wire:click="closeProfile" class="rounded-full p-1 text-slate-400 hover:bg-slate-100 hover:text-slate-600 transition">&times;</button>
                    </div>

                    @if ($summary)
                        <div class="mt-4 grid grid-cols-2 gap-2 text-sm">
                            <div class="rounded-2xl bg-slate-50 p-3 border border-slate-100">
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Base Salary</span>
                                <span class="font-mono font-bold text-slate-800 text-xs sm:text-sm">UGX {{ number_format($summary['salary']) }}</span>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3 border border-slate-100">
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Received</span>
                                <span class="font-mono font-bold text-slate-800 text-xs sm:text-sm">UGX {{ number_format($summary['received']) }}</span>
                            </div>
                            <div class="rounded-2xl bg-slate-50 p-3 border border-slate-100">
                                <span class="block text-xs font-bold uppercase tracking-wider text-slate-400">Advances</span>
                                <span class="font-mono font-bold text-slate-800 text-xs sm:text-sm">UGX {{ number_format($summary['advances']) }}</span>
                            </div>
                            <div class="rounded-2xl bg-emerald-50/80 p-3 border border-emerald-100">
                                <span class="block text-xs font-bold uppercase tracking-wider text-emerald-600">Remaining</span>
                                <span class="font-mono font-bold text-emerald-700 text-xs sm:text-sm">UGX {{ number_format($summary['remaining']) }}</span>
                            </div>
                        </div>
                    @endif

                    <form wire:submit="recordPayment" class="mt-5 space-y-4">
                        <!-- Payment Type -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Payment Type</label>
                            <div class="relative">
                                <select wire:model.live="paymentType" 
                                        class="w-full appearance-none rounded-xl border @error('paymentType') border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 pl-3.5 pr-10 text-sm text-slate-800 font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                                    <option value="salary">Salary</option>
                                    <option value="advance">Advance</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            @error('paymentType') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        <!-- Amount -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Amount</label>
                            <div class="relative rounded-xl shadow-xs">
                                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5">
                                    <span class="text-xs font-bold text-slate-400">UGX</span>
                                </div>
                                <input wire:model="amount" 
                                       type="number" 
                                       step="0.01" 
                                       min="0.01" 
                                       placeholder="0.00" 
                                       class="w-full rounded-xl border @error('amount') border-rose-300 bg-rose-50/30 text-rose-900 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 text-slate-800 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 pl-12 pr-4 text-sm font-mono transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4 placeholder:text-slate-300">
                            </div>
                            @error('amount') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        <!-- Payment Method -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Payment Method</label>
                            <div class="relative">
                                <select wire:model.live="method" 
                                        class="w-full appearance-none rounded-xl border @error('method') border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 pl-3.5 pr-10 text-sm text-slate-800 font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank">Bank</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                                </div>
                            </div>
                            @error('method') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        <div><label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Payment account</label><select wire:model="financialAccountId" class="w-full rounded-xl border border-slate-200 bg-slate-50/50 py-2.5 px-3.5 text-sm"><option value="">Select mapped account</option>@foreach($paymentAccounts->where('type',$method) as $account)<option value="{{ $account->id }}">{{ $account->name }} · {{ $account->currency }}</option>@endforeach</select>@error('financialAccountId')<p class="mt-1 text-xs text-rose-600">{{ $message }}</p>@enderror</div>

                        @if ($method === 'mobile_money')
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Transaction ID</label>
                                <input wire:model="transactionId" 
                                       type="text" 
                                       placeholder="e.g. MM12345678" 
                                       class="w-full rounded-xl border @error('transactionId') border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 px-3.5 text-sm text-slate-800 transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4 placeholder:text-slate-400 font-mono">
                                @error('transactionId') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                            </div>
                        @endif

                        @if ($method === 'bank')
                            <div>
                                <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Bank Slip Number</label>
                                <input wire:model="bankSlipNumber" 
                                       type="text" 
                                       placeholder="e.g. SLIP-9901" 
                                       class="w-full rounded-xl border @error('bankSlipNumber') border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 px-3.5 text-sm text-slate-800 transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4 placeholder:text-slate-400 font-mono">
                                @error('bankSlipNumber') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                            </div>
                        @endif

                        <!-- Paid On -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">Paid On</label>
                            <input wire:model="paidOn" 
                                   type="date" 
                                   class="w-full rounded-xl border @error('paidOn') border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror py-2.5 px-3.5 text-sm text-slate-800 font-medium transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4">
                            @error('paidOn') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        <!-- Notes -->
                        <div>
                            <label class="block text-[11px] font-semibold uppercase tracking-wider text-slate-500 mb-1.5">
                                Notes <span class="font-normal text-slate-400 lowercase">(optional)</span>
                            </label>
                            <textarea wire:model="notes" 
                                      rows="2" 
                                      placeholder="Optional comments..." 
                                      class="w-full rounded-xl border @error('notes') border-rose-300 bg-rose-50/30 focus:border-rose-500 focus:ring-rose-500/10 @else border-slate-200 bg-slate-50/50 hover:bg-white focus:border-amber-500 focus:ring-amber-500/10 @enderror p-3 text-sm text-slate-800 transition-all duration-200 focus:bg-white focus:outline-none focus:ring-4 placeholder:text-slate-400 resize-none"></textarea>
                            @error('notes') <p class="mt-1.5 flex items-center gap-1 text-xs font-medium text-rose-600"><svg class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>{{ $message }}</p> @enderror
                        </div>

                        <button wire:loading.attr="disabled" class="w-full rounded-xl bg-amber-400 hover:bg-amber-500 text-slate-950 font-bold py-3 transition shadow-sm hover:shadow active:scale-[0.99] flex items-center justify-center gap-2">
                            <span wire:loading.remove>Record Payment</span>
                            <span wire:loading class="flex items-center gap-2">
                                <svg class="animate-spin h-4 w-4 text-slate-950" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                Saving Payment...
                            </span>
                        </button>
                    </form>
                </section>

                <!-- Individual Payment History -->
                @if ($employeePayments->isNotEmpty())
                    <section class="rounded-3xl border border-slate-200/80 bg-white p-6 shadow-sm">
                        <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Recent Disbursements</h3>
                        <p class="text-sm font-extrabold text-slate-900 mt-0.5">{{ $selectedStaff->name }}</p>
                        
                        <ul class="mt-4 divide-y divide-slate-100 text-sm">
                            @foreach ($employeePayments as $payment)
                                <li class="flex items-center justify-between py-2.5">
                                    <div>
                                        <span class="font-bold text-slate-800 block">{{ ucfirst($payment->payment_type) }}</span>
                                        <span class="text-xs text-slate-400">{{ $payment->paid_at->format('d M Y') }} · {{ str_replace('_', ' ', ucfirst($payment->method)) }}</span>
                                    </div>
                                    <span class="font-mono font-bold text-slate-900">
                                        <span class="text-xs text-slate-400 font-normal">UGX</span> {{ number_format($payment->amount) }}
                                    </span>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        @endif
    </div>

    <!-- Overall Recent Activity -->
    @if (! $selectedStaff && $recentPayments->isNotEmpty())
        <section class="overflow-hidden rounded-3xl border border-slate-200/80 bg-white shadow-sm">
            <div class="border-b border-slate-100 px-6 py-5">
                <h2 class="text-lg font-extrabold text-slate-900">Recent Payroll Activity</h2>
                <p class="text-xs text-slate-500 mt-0.5">Global audit trail for salary &amp; advance disbursements</p>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600">
                    <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500 tracking-wider">
                        <tr>
                            <th class="px-6 py-3.5">Staff</th>
                            <th class="px-6 py-3.5">Type</th>
                            <th class="px-6 py-3.5">Amount</th>
                            <th class="px-6 py-3.5">Method</th>
                            <th class="px-6 py-3.5">Paid On</th>
                            <th class="px-6 py-3.5">Recorded By</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 font-medium">
                        @foreach ($recentPayments as $payment)
                            <tr class="hover:bg-slate-50/60 transition">
                                <td class="px-6 py-4 font-bold text-slate-900">
                                    {{ $payment->staff?->name ?? '—' }}
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-bold
                                        {{ $payment->payment_type === 'salary' ? 'bg-blue-50 text-blue-700 ring-1 ring-blue-600/20' : 'bg-purple-50 text-purple-700 ring-1 ring-purple-600/20' }}">
                                        {{ ucfirst($payment->payment_type) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-mono font-bold text-slate-800 whitespace-nowrap">
                                    <span class="text-xs text-slate-400 font-normal">UGX</span> {{ number_format($payment->amount) }}
                                </td>
                                <td class="px-6 py-4 capitalize text-slate-600">
                                    {{ str_replace('_', ' ', $payment->method) }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-slate-600">
                                    {{ $payment->paid_at->format('d M Y') }}
                                </td>
                                <td class="px-6 py-4 text-slate-500 font-normal">
                                    {{ $payment->recordedBy?->name ?? 'System' }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>
