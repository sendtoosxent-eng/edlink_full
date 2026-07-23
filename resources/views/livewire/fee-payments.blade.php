<div class="mx-auto w-full max-w-7xl space-y-6">
    <!-- TOP HEADER WITH BACK NAVIGATION -->
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <button onclick="window.history.back()" type="button" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-700 hover:bg-slate-100 transition shadow-2xs">
                <svg class="w-5 h-5 flex-shrink-0 transition-transform" :class="$store.ui?.collapsed && 'rotate-180'" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                </svg>
            </button>
            <div>
                <a href="javascript:history.back()" class="inline-flex items-center gap-1 text-[11px] font-bold text-yellow-600 hover:text-yellow-700 transition">
                    <span>Back to previous page</span>
                </a>
                <h1 class="text-xl font-bold tracking-tight text-slate-900">Fee Payments</h1>
            </div>
        </div>
        <p class="text-xs text-slate-500 max-w-md">Select a learner to review their financial profile, check balance, and record a payment.</p>
    </div>
    <!-- FLASH MESSAGES -->
    @if(session('status'))
        <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-3.5 text-xs font-semibold text-emerald-800 flex items-center gap-2 shadow-2xs">
            <i class="fa fa-check-circle text-emerald-500"></i>
            <span>{{ session('status') }}</span>
        </div>
    @endif 

    @if(session('error'))
        <div class="rounded-xl bg-rose-50 border border-rose-200 p-3.5 text-xs font-semibold text-rose-800 flex items-center gap-2 shadow-2xs">
            <i class="fa fa-exclamation-circle text-rose-500"></i>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    <div class="grid gap-6 lg:grid-cols-2 items-start">
        
        <!-- LEFT COLUMN: STUDENT SEARCH & SELECTOR CARD -->
        <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400">
            <div class="border-b border-slate-100 p-5 bg-slate-50/50">
                <div class="flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Select Student</h2>
                    <span class="text-[11px] font-semibold text-slate-400">Search learner</span>
                </div>
                
                <div class="relative mt-3">
                    <i class="fa fa-search absolute left-3.5 top-3 text-slate-400 text-xs"></i>
                    <input wire:model.live.debounce.300ms="search" placeholder="Search name or admission number..." class="w-full text-xs pl-9 pr-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-medium text-slate-900 transition placeholder:text-slate-400">
                </div>
            </div>

            <div class="max-h-[580px] overflow-y-auto divide-y divide-slate-100">
                @forelse($students as $student)
                    <button wire:click="openPaymentForm({{ $student->id }})" class="flex w-full items-center justify-between p-4 text-left transition hover:bg-yellow-50/60 {{ $payingStudentId === $student->id ? 'bg-yellow-50/80 border-l-4 border-l-yellow-400' : '' }}">
                        <div class="min-w-0 pr-2">
                            <span class="block text-xs font-bold text-slate-900 truncate">{{ $student->name }}</span>
                            <span class="text-[11px] text-slate-500 mt-0.5 block font-mono">{{ $student->admission_no }} · {{ $student->schoolClass?->name ?? 'Unassigned' }}</span>
                        </div>
                        <span class="inline-flex items-center gap-1 text-[11px] font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-300 px-3 py-1.5 rounded-xl shadow-2xs shrink-0 transition">
                            <span>View account</span>
                            <i class="fa fa-chevron-right text-[9px]"></i>
                        </span>
                    </button>
                @empty
                    <div class="p-12 text-center text-xs text-slate-400 font-medium italic">
                        <i class="fa fa-user-slash text-slate-300 text-xl block mb-2"></i>
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
        <section class="relative rounded-2xl border border-slate-200 bg-white p-6 shadow-sm overflow-hidden before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400">
            @if($payingStudentId) 
                @php($student=$students->firstWhere('id',$payingStudentId) ?? \App\Models\Student::find($payingStudentId)) 
                @php($fee=$student?->mappedFeeAmount($term) ?? 0) 
                @php($arrears=$student?->arrearsDueIn($term) ?? 0) 
                @php($paid=$student?->totalPaid($term) ?? 0) 
                @php($balance=$student?->balance($term) ?? 0)
                
                <div class="flex items-start justify-between pb-4 border-b border-slate-100">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-wider text-slate-400 block">Financial Profile</span>
                        <h2 class="mt-0.5 text-base font-bold text-slate-900">{{ $student->name }}</h2>
                        <p class="text-xs text-slate-500 font-mono mt-0.5">{{ $student->admission_no }} · {{ $student->schoolClass?->name }}</p>
                    </div>
                    <button wire:click="cancelPayment" class="inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-slate-600 bg-slate-100 hover:bg-slate-200 px-3 py-1.5 rounded-xl transition">
                        <i class="fa fa-times text-[10px]"></i>
                        <span>Close</span>
                    </button>
                </div>

                <!-- SUMMARY BADGES GRID -->
                <div class="mt-5 grid grid-cols-2 gap-3">
                    <div class="rounded-xl bg-slate-50 border border-slate-200/80 p-3">
                        <p class="text-[11px] font-semibold text-slate-500">Expected this term</p>
                        <p class="mt-1 text-sm font-extrabold text-slate-900 font-mono">UGX {{number_format($fee)}}</p>
                    </div>
                    <div class="rounded-xl bg-rose-50 border border-rose-200/60 p-3">
                        <p class="text-[11px] font-semibold text-rose-600">Arrears</p>
                        <p class="mt-1 text-sm font-extrabold text-rose-700 font-mono">UGX {{number_format($arrears)}}</p>
                    </div>
                    <div class="rounded-xl bg-emerald-50 border border-emerald-200/60 p-3">
                        <p class="text-[11px] font-semibold text-emerald-600">Paid so far</p>
                        <p class="mt-1 text-sm font-extrabold text-emerald-700 font-mono">UGX {{number_format($paid)}}</p>
                    </div>
                    <div class="rounded-xl bg-yellow-50 border border-yellow-200/80 p-3">
                        <p class="text-[11px] font-semibold text-yellow-800">Remaining Balance</p>
                        <p class="mt-1 text-sm font-extrabold text-yellow-950 font-mono">UGX {{number_format($balance)}}</p>
                    </div>
                </div>

                <!-- PAYMENT INPUT FORM -->
                <form wire:submit="recordPayment" class="mt-6 space-y-4 border-t border-slate-100 pt-5">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Amount (UGX)</label>
                        <input wire:model="amount" type="number" min="1" placeholder="Enter amount paid" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-bold text-slate-900 transition placeholder:font-normal">
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">Payment Method</label>
                        <select wire:model.live="method" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-semibold text-slate-800 transition">
                            <option value="cash">Cash</option>
                            <option value="mobile_money">Mobile money</option>
                            <option value="bank">Bank</option>
                            <option value="other">Other</option>
                        </select>
                    </div>

                    @if($method==='mobile_money')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Mobile Money Transaction ID</label>
                            <input wire:model="transaction_id" placeholder="e.g. 1827364590" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-mono text-slate-900 transition">
                        </div>
                    @endif 

                    @if($method==='bank')
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1.5">Bank Slip / Bank Reference Number</label>
                            <input wire:model="bank_slip_number" placeholder="e.g. SLIP-99881" class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-mono text-slate-900 transition">
                        </div>
                    @endif

                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1.5">
                            Notes <span class="font-normal text-slate-400">(optional)</span>
                        </label>
                        <textarea wire:model="notes" rows="2" placeholder="Add extra transaction details..." class="w-full text-xs px-3.5 py-2.5 bg-slate-50 border border-slate-200 rounded-xl focus:outline-none focus:bg-white focus:ring-2 focus:ring-yellow-400 font-medium text-slate-900 transition"></textarea>
                    </div>

                    <button type="submit" class="w-full inline-flex items-center justify-center gap-2 rounded-xl bg-yellow-400 hover:bg-yellow-300 py-3 text-xs font-bold text-slate-950 transition shadow-xs">
                        <i class="fa fa-print text-xs"></i>
                        <span>Record payment & print receipt</span>
                    </button>
                </form>
            @else
                <div class="flex h-full min-h-[360px] flex-col items-center justify-center text-center p-6 text-slate-400">
                    <div class="w-12 h-12 rounded-full bg-slate-100 flex items-center justify-center text-slate-400 mb-3 border border-slate-200">
                        <i class="fa fa-hand-pointer-o text-lg"></i>
                    </div>
                    <span class="text-xs font-bold text-slate-700">No Student Selected</span>
                    <p class="text-[11px] text-slate-400 max-w-xs mt-1">Choose a student from the list on the left to view expected fees, arrears, and process payments.</p>
                </div>
            @endif
        </section>
    </div>

    <!-- PREVIOUS PAYMENTS TABLE SECTION -->
    <section class="relative overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm before:absolute before:top-0 before:left-0 before:right-0 before:h-1.5 before:bg-yellow-400">
        <div class="border-b border-slate-100 p-5 bg-slate-50/50 flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-900">Recent Payment Transactions</h2>
            <span class="text-xs font-semibold text-slate-500">History Log</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-xs text-left">
                <thead class="bg-slate-50 border-b border-slate-200 text-slate-700 font-bold uppercase tracking-wider text-[10px]">
                    <tr>
                        <th class="px-5 py-3.5">Student</th>
                        <th class="px-5 py-3.5">Term</th>
                        <th class="px-5 py-3.5">Method / Ref</th>
                        <th class="px-5 py-3.5 text-right">Amount (UGX)</th>
                        <th class="px-5 py-3.5 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                    @forelse($payments as $payment)
                        <tr class="hover:bg-slate-50/80 transition">
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
                            <td class="px-5 py-3.5 text-right space-x-2">
                                <a target="_blank" href="{{route('fee-payments.receipt',$payment)}}" class="inline-flex items-center gap-1 text-[11px] font-bold text-yellow-700 hover:text-yellow-800 bg-yellow-50 hover:bg-yellow-100 px-2.5 py-1 rounded-lg border border-yellow-200/80 transition">
                                    <i class="fa fa-receipt text-[10px]"></i>
                                    <span>Receipt</span>
                                </a>
                                @if($term && $term->isOpen() && $payment->term_id===$term->id)
                                    <button wire:click="deletePayment({{$payment->id}})" wire:confirm="Are you sure you want to delete this payment record?" class="inline-flex items-center gap-1 text-[11px] font-bold text-rose-600 hover:text-rose-700 bg-rose-50 hover:bg-rose-100 px-2.5 py-1 rounded-lg border border-rose-200/60 transition">
                                        <i class="fa fa-trash text-[10px]"></i>
                                        <span>Delete</span>
                                    </button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="p-8 text-center text-slate-400 italic">
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