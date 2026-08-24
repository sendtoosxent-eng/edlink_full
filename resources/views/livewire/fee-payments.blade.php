<div class="space-y-6">
        <!-- DARK BRANDED HEADER CARD WITH BACK NAVIGATION -->
    <div class="relative overflow-hidden rounded-3xl bg-[#252641] p-6 text-white shadow-md sm:p-8">
        <div class="relative z-10 flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-start gap-4">
                <!-- Back Button -->
                
                <div class="space-y-1.5">
                    <div class="flex items-center gap-2">
                        
                    </div>
                    <h1 class="text-2xl font-black tracking-tight text-amber-300 sm:text-3xl">
                        Fee Payments
                    </h1>
                    <p class="text-xs text-slate-400 max-w-md">
                        Select a learner to review their financial profile, check balance, and record a payment.
                    </p>
                </div>
            </div>

            <div class="flex flex-col items-start gap-3 sm:items-end">
            @if($canApproveAdjustments)
                <a href="{{ url('/finance/payments?screen=adjustments') }}" class="inline-flex items-center gap-2 rounded-xl bg-violet-500 px-4 py-2.5 text-xs font-black text-white shadow-sm hover:bg-violet-400">
                    Review Fee Adjustments
                    @if($pendingAdjustments->isNotEmpty())<span class="rounded-full bg-amber-300 px-2 py-0.5 text-[10px] text-slate-950">{{ $pendingAdjustments->count() }}</span>@endif
                </a>
            @endif
            @if (isset($term) && $term)
                <div class="inline-flex items-center gap-3 rounded-2xl border border-white/10 bg-white/10 px-4 py-3 shadow-xs backdrop-blur-md">
                    <span class="relative flex h-2.5 w-2.5">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                    </span>
                    <div class="text-xs">
                        <span class="block font-bold uppercase tracking-wider text-[10px] text-yellow-300">Active Term</span>
                        <strong class="font-bold text-white">{{ $term->name }}, {{ $term->year }}</strong>
                    </div>
                </div>
            @endif
            </div>
        </div>

        <!-- Ambient Glow Effects -->
        <div class="absolute -right-10 -top-10 h-48 w-48 rounded-full bg-amber-500/10 blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 h-48 w-48 rounded-full bg-indigo-500/10 blur-3xl pointer-events-none"></div>
    </div>

    <!-- FLASH MESSAGES -->
    @if(session('status'))
        <div class="flex items-center gap-3 rounded-2xl border border-emerald-200 bg-emerald-50/90 p-4 text-sm font-semibold text-emerald-900 shadow-xs">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span>{{ session('status') }}</span>
        </div>
    @endif 

    @if(session('error'))
        <div class="flex items-center gap-3 rounded-2xl border border-rose-200 bg-rose-50/90 p-4 text-sm font-semibold text-rose-900 shadow-xs">
            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-xl bg-rose-100 text-rose-700">
                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                </svg>
            </div>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 items-start">
        
        <!-- LEFT COLUMN: STUDENT SEARCH & SELECTOR CARD -->
        <section class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
            <div class="border-b border-slate-100 p-5 bg-slate-50/50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Select Student</h2>
                    <span class="text-[11px] font-semibold text-slate-400">Search learner</span>
                </div>
                
                <div class="relative mt-3">
                    <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" placeholder="Search name or admission number..." class="w-full text-xs pl-10 pr-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-900 transition placeholder:text-slate-400">
                </div>
            </div>

            <div class="max-h-[580px] overflow-y-auto divide-y divide-slate-100">
                @forelse($students as $student)
                    <button wire:click="openPaymentForm({{ $student->id }})" class="flex w-full items-center justify-between p-4 text-left transition hover:bg-amber-50/40 {{ $payingStudentId === $student->id ? 'bg-amber-50/80 border-l-4 border-l-amber-500' : '' }}">
                        <div class="min-w-0 pr-2 flex items-center gap-3">
                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-amber-500/10 text-xs font-bold text-amber-700 ring-1 ring-inset ring-amber-500/20">
                                {{ strtoupper(substr($student->name, 0, 1)) }}
                            </div>
                            <div class="truncate">
                                <span class="block text-xs font-bold text-slate-900 truncate">{{ $student->name }}</span>
                                <span class="text-[11px] text-slate-500 mt-0.5 block font-mono">{{ $student->admission_no }} · {{ $student->schoolClass?->name ?? 'Unassigned' }}</span>
                            </div>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-950 bg-amber-400 hover:bg-amber-300 px-3 py-1.5 rounded-xl shadow-2xs shrink-0 transition">
                            <span>View account</span>
                            <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7" />
                            </svg>
                        </span>
                    </button>
                @empty
                    <div class="p-12 text-center text-xs text-slate-400 font-medium">
                        <div class="mx-auto mb-2 flex h-10 w-10 items-center justify-center rounded-full bg-slate-100 text-slate-400">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                            </svg>
                        </div>
                        No active learners found matching your search.
                    </div>
                @endforelse
            </div>

            @if($students->hasPages())
                <div class="border-t border-slate-100 p-3 bg-slate-50/50">
                    {{ $students->links() }}
                </div>
            @endif
        </section>

        <!-- RIGHT COLUMN: FINANCIAL PROFILE & PAYMENT FORM -->
        <section class="relative rounded-2xl border border-slate-200/80 bg-white p-6 shadow-xs overflow-hidden">
            @if($payingStudentId) 
                @php($student=$selectedStudent)
                @php($fee=$student?->mappedFeeAmount($term) ?? 0) 
                @php($adjustments=$student?->feeAdjustmentTotal($term) ?? 0)
                @php($adjustedFee=$student?->adjustedFeeAmount($term) ?? 0)
                @php($arrears=$student?->arrearsDueIn($term) ?? 0) 
                @php($paid=$student?->totalPaid($term) ?? 0) 
                @php($balance=$student?->balance($term) ?? 0)
                
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Financial Profile</span>
                        <h2 class="mt-0.5 text-base font-bold text-slate-900">{{ $student->name }}</h2>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $student->admission_no }} · {{ $student->schoolClass?->name }}</p>
                    </div>
                    <button wire:click="cancelPayment" class="inline-flex items-center gap-1 text-xs font-bold text-slate-500 hover:text-slate-700 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-xl transition">
                        <svg class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Close</span>
                    </button>
                </div>

                <!-- SUMMARY BADGES GRID -->
                <div class="mt-5 grid grid-cols-2 gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl bg-slate-50 border border-slate-200/80 p-3">
                        <p class="text-[11px] font-semibold text-slate-500">Expected this term</p>
                        <p class="mt-1 text-sm font-black text-slate-900 font-mono">UGX {{number_format($fee)}}</p>
                    </div>
                    <div class="rounded-2xl bg-violet-50/80 border border-violet-200/60 p-3">
                        <p class="text-[11px] font-semibold text-violet-600">Approved adjustments</p>
                        <p class="mt-1 text-sm font-black text-violet-700 font-mono">- UGX {{number_format($adjustments)}}</p>
                    </div>
                    <div class="rounded-2xl bg-blue-50/80 border border-blue-200/60 p-3">
                        <p class="text-[11px] font-semibold text-blue-600">Adjusted term fee</p>
                        <p class="mt-1 text-sm font-black text-blue-700 font-mono">UGX {{number_format($adjustedFee)}}</p>
                    </div>
                    <div class="rounded-2xl bg-rose-50/80 border border-rose-200/60 p-3">
                        <p class="text-[11px] font-semibold text-rose-600">Arrears</p>
                        <p class="mt-1 text-sm font-black text-rose-700 font-mono">UGX {{number_format($arrears)}}</p>
                    </div>
                    <div class="rounded-2xl bg-emerald-50/80 border border-emerald-200/60 p-3">
                        <p class="text-[11px] font-semibold text-emerald-600">Paid so far</p>
                        <p class="mt-1 text-sm font-black text-emerald-700 font-mono">UGX {{number_format($paid)}}</p>
                    </div>
                    <div class="rounded-2xl bg-amber-50/80 border border-amber-200/80 p-3">
                        <p class="text-[11px] font-semibold text-amber-800">Remaining Balance</p>
                        <p class="mt-1 text-sm font-black text-amber-950 font-mono">UGX {{number_format($balance)}}</p>
                    </div>
                </div>

                @if($canRequestAdjustments && $term?->isEditable())
                    <details class="mt-5 rounded-2xl border border-violet-200 bg-violet-50/40 p-4">
                        <summary class="cursor-pointer text-xs font-black uppercase tracking-wider text-violet-800">Request individual fee adjustment</summary>
                        <form wire:submit="requestAdjustment" class="mt-4 space-y-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <label class="text-xs font-semibold text-slate-700">Reason type
                                    <select wire:model="adjustmentType" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs">
                                        <option value="negotiated">Negotiated fee</option><option value="waiver">Waiver</option><option value="scholarship">Scholarship</option><option value="staff_child">Staff-child benefit</option><option value="correction">Correction</option>
                                    </select>
                                </label>
                                <label class="text-xs font-semibold text-slate-700">Calculation
                                    <select wire:model.live="adjustmentCalculation" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs">
                                        <option value="fixed">Fixed deduction</option><option value="percentage">Percentage discount</option><option value="final_fee">Final agreed fee</option>
                                    </select>
                                </label>
                            </div>
                            <label class="block text-xs font-semibold text-slate-700">{{ $adjustmentCalculation === 'percentage' ? 'Percentage' : ($adjustmentCalculation === 'final_fee' ? 'Final fee (UGX)' : 'Deduction amount (UGX)') }}
                                <input wire:model="adjustmentValue" type="number" min="0.01" step="0.01" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs font-bold">
                                @error('adjustmentValue')<span class="mt-1 block text-xs text-rose-600">{{$message}}</span>@enderror
                            </label>
                            <label class="block text-xs font-semibold text-slate-700">Reason and supporting details
                                <textarea wire:model="adjustmentReason" rows="3" class="mt-1.5 w-full rounded-xl border-slate-200 bg-white text-xs" placeholder="Explain why this learner should receive a different fee..."></textarea>
                                @error('adjustmentReason')<span class="mt-1 block text-xs text-rose-600">{{$message}}</span>@enderror
                            </label>
                            <button class="rounded-xl bg-violet-700 px-4 py-2.5 text-xs font-bold text-white hover:bg-violet-600">Submit for approval</button>
                        </form>
                    </details>
                @endif

                @if($student?->feeAdjustments->isNotEmpty())
                    <div class="mt-4 space-y-2">
                        <p class="text-[10px] font-black uppercase tracking-wider text-slate-400">Adjustment history</p>
                        @foreach($student->feeAdjustments->where('term_id', $term?->id)->sortByDesc('created_at') as $adjustment)
                            <div class="rounded-xl border border-slate-200 p-3 text-xs">
                                <div class="flex items-center justify-between gap-3"><b>{{ ucwords(str_replace('_',' ',$adjustment->type)) }} · UGX {{ number_format($adjustment->amount) }}</b><span class="rounded-full px-2 py-0.5 font-bold {{ $adjustment->status === 'approved' ? 'bg-emerald-100 text-emerald-700' : ($adjustment->status === 'rejected' ? 'bg-rose-100 text-rose-700' : 'bg-amber-100 text-amber-700') }}">{{ ucfirst($adjustment->status) }}</span></div>
                                <p class="mt-1 text-slate-600">{{ $adjustment->reason }}</p>
                                <p class="mt-1 text-[10px] text-slate-400">Requested by {{ $adjustment->requester?->name ?? 'Former user' }}{{ $adjustment->reviewer ? ' · Reviewed by '.$adjustment->reviewer->name : '' }}</p>
                                @if($canApproveAdjustments && $adjustment->status === 'approved' && $term?->isEditable())<button wire:click="cancelAdjustment({{$adjustment->id}})" wire:confirm="Cancel this approved adjustment and restore the learner's fee balance?" class="mt-2 text-[10px] font-bold text-rose-600 hover:text-rose-700">Cancel approved adjustment</button>@endif
                            </div>
                        @endforeach
                    </div>
                @endif

                <!-- PAYMENT INPUT FORM -->
                @if($canRecordPayments)
                <form wire:submit="recordPayment" class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount (UGX)</label>
                        <input wire:model="amount" type="number" min="1" placeholder="Enter amount paid" class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-bold text-slate-900 transition placeholder:font-normal">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Method</label>
                        <select wire:model.live="method" class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-semibold text-slate-800 transition">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile money</option>
                            <option value="bank">Bank</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    @if($method==='mobile_money')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mobile Money Transaction ID</label>
                            <input wire:model="transaction_id" placeholder="e.g. 1827364590" class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-mono text-slate-900 transition">
                        </div>
                    @endif 

                    @if($method==='bank')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank Slip / Bank Reference Number</label>
                            <input wire:model="bank_slip_number" placeholder="e.g. SLIP-99881" class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-mono text-slate-900 transition">
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Notes <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <textarea wire:model="notes" rows="2" placeholder="Add extra transaction details..." class="w-full text-xs px-3.5 py-2.5 bg-slate-50/50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:border-amber-500 focus:ring-4 focus:ring-amber-500/10 font-medium text-slate-900 transition"></textarea>
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-amber-400 hover:bg-amber-300 py-3 text-xs font-bold text-slate-950 transition shadow-xs active:scale-95">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                        </svg>
                        <span>Record payment & print receipt</span>
                    </button>
                </form>
                @endif
            @else
                <div class="flex h-full min-h-[360px] flex-col items-center justify-center text-center p-6 text-slate-400">
                    <div class="w-12 h-12 rounded-2xl bg-slate-100 flex items-center justify-center text-slate-400 mb-3 border border-slate-200/80">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122" />
                        </svg>
                    </div>
                    <span class="text-xs font-bold text-slate-700">No Student Selected</span>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-1">Choose a student from the list on the left to view expected fees, arrears, and process payments.</p>
                </div>
            @endif
        </section>
    </div>

    <!-- PREVIOUS PAYMENTS TABLE SECTION -->
    <section class="relative overflow-hidden rounded-2xl border border-slate-200/80 bg-white shadow-xs">
        <div class="border-b border-slate-100 p-5 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Recent Payment Transactions</h2>
            <span class="text-xs font-semibold text-slate-500">History Log</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50/80 border-b border-slate-200/80 text-slate-500 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-5 py-3.5">Student</th>
                        <th class="px-5 py-3.5">Term</th>
                        <th class="px-5 py-3.5">Method / Ref</th>
                        <th class="px-5 py-3.5 text-right">Amount (UGX)</th>
                        @if($canRecordPayments)<th class="px-5 py-3.5 text-right">Actions</th>@endif
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/70 transition">
                            <td class="px-5 py-3.5 font-bold text-slate-900">{{ $payment->student->name }}</td>
                            <td class="px-5 py-3.5 text-slate-600">{{ $payment->term->name }}, {{ $payment->term->year }}</td>
                            <td class="px-5 py-3.5 text-slate-500">
                                <span class="inline-flex items-center gap-1 font-semibold text-slate-700">
                                    {{ ucwords(str_replace('_',' ',$payment->method)) }}
                                </span>
                                @if($payment->transaction_id || $payment->bank_slip_number)
                                    <span class="block font-mono text-[10px] text-slate-400">{{ $payment->transaction_id ?? $payment->bank_slip_number }}</span>
                                @endif
                            </td>
                            <td class="px-5 py-3.5 text-right font-mono font-extrabold text-slate-900">
                                {{ number_format($payment->amount) }}
                            </td>
                            @if($canRecordPayments)<td class="px-5 py-3.5 text-right space-x-2">
                                <a target="_blank" href="{{route('fee-payments.receipt',$payment)}}" class="inline-flex items-center gap-1 text-[11px] font-bold text-amber-800 hover:text-amber-900 bg-amber-50 hover:bg-amber-100 px-2.5 py-1 rounded-xl border border-amber-200/80 transition">
                                    <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                    </svg>
                                    <span>Receipt</span>
                                </a>
                                @if(isset($term) && $term && $term->isOpen() && $payment->term_id===$term->id)
                                    <button wire:click="deletePayment({{$payment->id}})" wire:confirm="Are you sure you want to delete this payment record?" class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-xl border border-rose-200/60 transition">
                                        <svg class="h-3 w-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                        <span>Delete</span>
                                    </button>
                                @endif
                            </td>@endif
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $canRecordPayments ? 5 : 4 }}" class="p-8 text-center text-slate-400">
                                No payment records logged yet for this section.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>
</div>

<script>
    document.addEventListener('livewire:init', () => {
        Livewire.on('payment-recorded', e => window.open(e.receiptUrl, '_blank'));
    });
</script>
