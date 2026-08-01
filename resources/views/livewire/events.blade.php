<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}"><link rel="apple-touch-icon" href="{{ asset('img/fav.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt #{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }} — {{ $school->name }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        /* PORTRAIT PAGE SPECIFIC PRINT STYLES */
        @page {
            size: A4 portrait;
            margin: 12mm;
        }

        @media print {
            body {
                background: white !important;
                padding: 0 !important;
                margin: 0 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .portrait-receipt {
                box-shadow: none !important;
                border: 1px solid #cbd5e1 !important;
                margin: 0 auto !important;
                width: 100% !important;
                max-width: 100% !important;
                border-radius: 0 !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 p-4 md:p-8 min-h-screen">

    <!-- TOP ACTION BAR (Hidden on Print) -->
    <div class="max-w-[210mm] mx-auto mb-4 flex items-center justify-between no-print">
        <button onclick="window.history.back()" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs transition">
            <i class="fa fa-arrow-left"></i>
            <span>Back</span>
        </button>
        <button onclick="window.print()" class="inline-flex items-center gap-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-300 px-4 py-2 rounded-xl shadow-xs transition">
            <i class="fa fa-print"></i>
            <span>Print Receipt (Portrait)</span>
        </button>
    </div>

    <!-- PORTRAIT RECEIPT CARD -->
    <main class="portrait-receipt max-w-[210mm] mx-auto bg-white rounded-2xl border border-slate-200 shadow-md overflow-hidden relative before:absolute before:top-0 before:left-0 before:right-0 before:h-2 before:bg-yellow-400 p-6 md:p-10 space-y-6">
        
        <!-- 1. PORTRAIT HEADER: BADGE + SCHOOL INFO -->
        <header class="border-b border-slate-200 pb-6">
            <div class="flex items-start justify-between gap-4">
                <div class="flex items-center gap-4">
                    <!-- LOGO / BADGE -->
                    <div class="w-20 h-20 rounded-full border-2 border-yellow-400 bg-white flex-shrink-0 overflow-hidden shadow-2xs flex items-center justify-center">
                        @if($school->logo_url ?? false)
                            <img src="{{ asset($school->logo_url) }}" alt="School Badge" class="w-full h-full object-cover">
                        @else
                            <div class="w-full h-full flex items-center justify-center text-slate-400 bg-slate-100">
                                <i class="fa fa-university text-3xl text-slate-500"></i>
                            </div>
                        @endif
                    </div>

                    <!-- SCHOOL NAME & REG -->
                    <div>
                        <h1 class="text-2xl font-black text-slate-900 tracking-tight leading-tight">{{ $school->name }}</h1>
                        @if($school->motto)
                            <p class="text-xs italic text-yellow-700 font-bold mt-0.5">"{{ $school->motto }}"</p>
                        @endif
                        <p class="text-[11px] font-mono text-slate-500 mt-1">School Reg No: <span class="font-bold text-slate-700">{{ $school->school_number ?? 'N/A' }}</span></p>
                    </div>
                </div>

                <!-- RECEIPT META -->
                <div class="text-right flex-shrink-0">
                    <span class="inline-block text-[10px] font-black uppercase tracking-widest text-slate-950 bg-yellow-400 px-3 py-1 rounded-md mb-2">
                        Official Receipt
                    </span>
                    <div class="text-xs text-slate-400">Receipt No.</div>
                    <div class="text-lg font-black font-mono text-slate-900">#{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</div>
                </div>
            </div>

            <!-- CONTACT DETAILS (VERTICAL FRIENDLY GRID) -->
            <div class="mt-5 pt-4 border-t border-dashed border-slate-200 grid grid-cols-2 sm:grid-cols-4 gap-2 text-[11px] text-slate-600 bg-slate-50/80 p-3 rounded-xl">
                <div>
                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Phone</span>
                    <span class="font-bold text-slate-800">{{ $school->phone ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Email</span>
                    <span class="font-bold text-slate-800 truncate block">{{ $school->email ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Website</span>
                    <span class="font-bold text-slate-800 truncate block">{{ $school->website ?? 'N/A' }}</span>
                </div>
                <div>
                    <span class="text-slate-400 text-[10px] font-bold block uppercase">Address</span>
                    <span class="font-bold text-slate-800 truncate block">{{ $school->address ?? 'N/A' }}</span>
                </div>
            </div>
        </header>

        <!-- 2. STUDENT & TRANSACTION SPECIFICATIONS -->
        <section class="space-y-3">
            <h2 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Learner & Payment Information</h2>
            
            <div class="grid grid-cols-2 gap-x-6 gap-y-3 bg-slate-50 p-4 rounded-xl border border-slate-200 text-xs">
                <div>
                    <span class="text-slate-400 block text-[10px]">Student Name</span>
                    <span class="font-bold text-slate-900 text-sm">{{ $payment->student->name }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Reg / Admission Number</span>
                    <span class="font-mono font-bold text-slate-900 text-sm">{{ $payment->student->admission_no }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Class / Stream</span>
                    <span class="font-bold text-slate-800">{{ $payment->student->schoolClass?->name ?? '—' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Academic Term</span>
                    <span class="font-bold text-slate-800">{{ $payment->term->name }}, {{ $payment->term->year }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Payment Method</span>
                    <span class="font-bold text-slate-800 capitalize">{{ ucwords(str_replace('_', ' ', $payment->method)) }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Reference / Bank Slip No.</span>
                    <span class="font-mono font-bold text-slate-800">{{ $payment->transaction_id ?? $payment->bank_slip_number ?? 'Cash Payment' }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Date & Time Received</span>
                    <span class="font-bold text-slate-800">{{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</span>
                </div>

                <div>
                    <span class="text-slate-400 block text-[10px]">Issued By</span>
                    <span class="font-bold text-slate-800">{{ $payment->recordedBy?->name ?? 'Accounts Dept.' }}</span>
                </div>
            </div>
        </section>

        <!-- 3. FINANCIAL BREAKDOWN LEDGER TABLE -->
        <section class="space-y-2">
            <h2 class="text-[11px] font-bold uppercase tracking-wider text-slate-400">Financial Ledger Summary</h2>
            
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-xs text-left">
                    <thead class="bg-slate-100 text-slate-700 font-bold uppercase text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-2.5">Financial Item</th>
                            <th class="px-4 py-2.5 text-right">Amount (UGX)</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-slate-800 font-medium">
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Term Tuition / Fees Expected</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-slate-900">
                                {{ number_format($payment->student?->mappedFeeAmount($payment->term) ?? 0) }}
                            </td>
                        </tr>
                        <tr>
                            <td class="px-4 py-2.5 text-slate-600">Outstanding Arrears (Previous Terms)</td>
                            <td class="px-4 py-2.5 text-right font-mono font-bold text-rose-700">
                                {{ number_format($payment->student?->arrearsDueIn($payment->term) ?? 0) }}
                            </td>
                        </tr>
                        <tr class="bg-emerald-50/80 font-bold">
                            <td class="px-4 py-3 text-emerald-900 flex items-center gap-2">
                                <i class="fa fa-check-circle text-emerald-600"></i>
                                <span>Amount Paid This Receipt</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono text-base font-extrabold text-emerald-700">
                                {{ number_format($payment->amount) }}
                            </td>
                        </tr>
                        <tr class="bg-yellow-50/60 font-bold">
                            <td class="px-4 py-2.5 text-yellow-950">Current Remaining Balance</td>
                            <td class="px-4 py-2.5 text-right font-mono text-sm font-extrabold text-yellow-900">
                                {{ number_format($payment->student?->balance($payment->term) ?? 0) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <!-- REMARKS / NOTES (IF APPLICABLE) -->
        @if($payment->notes)
            <div class="p-3 bg-slate-50 rounded-xl border border-slate-200 text-xs text-slate-600">
                <span class="font-bold text-slate-800 block mb-0.5">Remarks / Payment Notes:</span>
                <p class="italic text-slate-500">{{ $payment->notes }}</p>
            </div>
        @endif

        <!-- 4. SIGNATURES & STAMP AREA (STACKED FOR PORTRAIT) -->
        <section class="pt-10 mt-6 border-t border-slate-200 grid grid-cols-2 gap-8 items-end">
            <div class="text-center">
                <div class="border-b border-dashed border-slate-300 pb-10"></div>
                <span class="text-xs font-bold text-slate-700 block mt-2">Bursar / Cashier Signature</span>
                <span class="text-[10px] text-slate-400 block mt-0.5">Date: ________________________</span>
            </div>

            <div class="text-center">
                <div class="border-b border-dashed border-slate-300 pb-10 flex items-center justify-center">
                    <span class="text-[10px] text-slate-300 uppercase tracking-widest font-bold">Official Stamp</span>
                </div>
                <span class="text-xs font-bold text-slate-700 block mt-2">School Official Stamp</span>
            </div>
        </section>

        <!-- 5. PORTRAIT FOOTER -->
        <footer class="pt-4 border-t border-slate-100 text-center text-xs text-slate-400">
            <p class="font-semibold text-slate-600">{{ $school->receipt_footer ?? "Thank you for your payment to {$school->name}." }}</p>
            <p class="text-[10px] text-slate-400 mt-0.5">Computer-generated official receipt • Edlink School Management System</p>
        </footer>

    </main>

</body>
</html>
