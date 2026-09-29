<!DOCTYPE html>
<html lang="en">
<head><link rel="icon" type="image/png" href="{{ asset('img/fav.png') }}"><link rel="apple-touch-icon" href="{{ asset('img/fav.png') }}">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Receipt {{ $payment->id }} — {{ $school->name }}</title>
    <!-- Google Fonts: Poppins -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;0,900;1,400;1,600&display=swap" rel="stylesheet">
    
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Poppins', 'sans-serif'],
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
        }
        @page {
            size: A4 portrait;
            margin: 0;
        }
        @media print {
            body {
                background: white !important;
                padding: 0 !important;
            }
            .receipt-container {
                box-shadow: none !important;
                border: none !important;
                width: 100% !important;
                max-width: 100% !important;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body class="bg-slate-100 font-sans text-slate-800 p-4 md:p-8 min-h-screen">

    <!-- PRINT CONTROL BUTTONS -->
    <div class="max-w-[210mm] mx-auto mb-4 flex items-center justify-between no-print">
        <button onclick="window.history.back()" class="inline-flex items-center gap-2 text-xs font-bold text-slate-600 hover:text-slate-900 bg-white border border-slate-200 px-3.5 py-2 rounded-xl shadow-2xs transition">
            <i class="fa fa-arrow-left"></i>
            <span>Back</span>
        </button>
        <button onclick="window.print()" class="inline-flex items-center gap-2 text-xs font-bold text-slate-950 bg-yellow-400 hover:bg-yellow-300 px-4 py-2 rounded-xl shadow-xs transition">
            <i class="fa fa-print"></i>
            <span>Print Receipt</span>
        </button>
    </div>

    <!-- MAIN RECEIPT CONTAINER -->
    <main class="receipt-container max-w-[210mm] mx-auto bg-white shadow-xl relative flex flex-col justify-between min-h-[297mm]">
        
        <div>
            <!-- 1. TOP HEADER BAR -->
            <header class="flex items-stretch justify-between relative overflow-hidden">
                <div class="w-6 bg-slate-900 flex-shrink-0"></div>

                <!-- Logo & School Branding -->
                <div class="flex items-center gap-4 py-6 px-6 flex-1">
                    <div class="w-16 h-16 rounded-full border-2 border-yellow-400 p-0.5 flex items-center justify-center flex-shrink-0 bg-white">
                        @if($school->logo_url ?? false)
                            <img src="{{ asset($school->logo_url) }}" class="w-full h-full object-cover rounded-full">
                        @else
                            <i class="fa fa-university text-slate-700 text-2xl"></i>
                        @endif
                    </div>
                    <div>
                        <h1 class="text-xl sm:text-2xl font-extrabold uppercase text-slate-900 tracking-tight leading-none">{{ $school->name }}</h1>
                        <p class="text-[11px] font-semibold text-yellow-600 tracking-wider uppercase mt-1">{{ $school->motto ?? 'Excellence and Integrity' }}</p>
                    </div>
                </div>

                <!-- Yellow Header Title Banner -->
                <div class="bg-yellow-400 text-slate-950 flex items-center justify-center px-8 sm:px-12 py-6">
                    <h2 class="text-xl sm:text-2xl font-black uppercase tracking-wider whitespace-nowrap">FEE RECEIPT</h2>
                </div>
            </header>

            <!-- 2. STUDENT & INVOICE META BOX -->
            <div class="px-8 mt-6">
                <div class="bg-slate-50 p-6 border border-slate-200 flex flex-col sm:flex-row justify-between gap-6">
                    <div>
                        <span class="text-[10px] font-bold uppercase tracking-widest text-slate-400 block mb-1">Received From</span>
                        <h3 class="text-lg font-bold text-slate-900 leading-snug">{{ $payment->student->name }}</h3>
                        <p class="text-xs font-semibold text-slate-700 mt-0.5">{{ $payment->student->schoolClass?->name ?? 'Class N/A' }} · {{ $school->name }}</p>
                        <p class="text-xs font-medium text-slate-600 mt-1"><strong class="font-bold text-slate-800">Reg No:</strong> {{ $payment->student->admission_no }}</p>
                        <p class="text-xs font-medium text-slate-600"><strong class="font-bold text-slate-800">Academic Term:</strong> {{ $payment->term->name }}, {{ $payment->term->year }}</p>
                    </div>

                    <div class="sm:text-right text-xs space-y-1.5 font-medium text-slate-700">
                        <p><strong class="font-bold text-slate-900">Receipt No:</strong> <span class="font-mono text-slate-900 font-extrabold text-sm">#{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</span></p>
                        <p><strong class="font-bold text-slate-900">Date Paid:</strong> {{ $payment->paid_at ? $payment->paid_at->format('d M Y, h:i A') : now()->format('d M Y, h:i A') }}</p>
                        <p><strong class="font-bold text-slate-900">Payment Status:</strong> <span class="text-emerald-700 font-bold uppercase bg-emerald-100 px-2.5 py-0.5 rounded text-[10px]">Paid</span></p>
                    </div>
                </div>
            </div>

            <!-- 3. FEES PAID TRANSACTIONS TABLE -->
            <div class="px-8 mt-8">
                <table class="w-full text-left text-xs border-2 border-slate-900 border-collapse">
                    <thead>
                        <tr class="bg-slate-900 text-white font-extrabold uppercase border-b-2 border-slate-900 text-[11px]">
                            <th class="p-3 border-r-2 border-slate-900 w-14 text-center">NO.</th>
                            <th class="p-3 border-r-2 border-slate-900">FEE PAYMENT DESCRIPTION</th>
                            <th class="p-3 text-right w-44">AMOUNT PAID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y-2 divide-slate-900 font-medium text-slate-800">
                        <tr>
                            <td class="p-3 border-r-2 border-slate-900 text-center font-bold text-slate-900">01</td>
                            <td class="p-3 border-r-2 border-slate-900 uppercase font-bold text-slate-900">
                                School Fees Payment ({{ ucwords(str_replace('_', ' ', $payment->method)) }})
                            </td>
                            <td class="p-3 text-right font-mono font-bold text-slate-900 text-sm">
                                UGX {{ number_format($payment->amount, 2) }}
                            </td>
                        </tr>
                        <!-- Structural Empty Rows -->
                        <tr>
                            <td class="p-3 border-r-2 border-slate-900 text-center font-bold text-slate-300">02</td>
                            <td class="p-3 border-r-2 border-slate-900 uppercase text-slate-300">—</td>
                            <td class="p-3 text-right font-mono text-slate-300">0.00</td>
                        </tr>
                        <tr>
                            <td class="p-3 border-r-2 border-slate-900 text-center font-bold text-slate-300">03</td>
                            <td class="p-3 border-r-2 border-slate-900 uppercase text-slate-300">—</td>
                            <td class="p-3 text-right font-mono text-slate-300">0.00</td>
                        </tr>
                        <tr>
                            <td class="p-3 border-r-2 border-slate-900 text-center font-bold text-slate-300">04</td>
                            <td class="p-3 border-r-2 border-slate-900 uppercase text-slate-300">—</td>
                            <td class="p-3 text-right font-mono text-slate-300">0.00</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. AMOUNT IN WORDS SECTION -->
            <div class="px-8 mt-4">
                <div class="p-3.5 bg-yellow-50/80 border border-yellow-300 rounded-none text-xs text-slate-900">
                    <span class="font-bold text-yellow-800 uppercase tracking-widest block text-[10px]">Amount Paid in Words:</span>
                    <p class="font-semibold text-slate-900 italic mt-0.5">
                        {{ ucfirst(Number::spell($payment->amount)) }} Ugandan Shillings Only.
                    </p>
                </div>
            </div>

            <!-- 5. PAYMENT METHOD & TOTALS -->
            <div class="px-8 mt-6 grid grid-cols-12 gap-8 items-start">
                
                <!-- Left Column -->
                <div class="col-span-7 space-y-4 text-xs">
                    <div>
                        <h4 class="font-bold text-slate-900 uppercase tracking-wider mb-1 text-[11px]">Payment Reference:</h4>
                        <div class="space-y-1 font-semibold text-slate-800 bg-slate-50 p-3 border border-slate-200">
                            <p>Method: <span class="font-bold text-slate-900 uppercase">{{ ucwords(str_replace('_', ' ', $payment->method)) }}</span></p>
                            <p>Ref / Slip ID: <span class="font-mono text-slate-900">{{ $payment->transaction_id ?? $payment->bank_slip_number ?? 'N/A' }}</span></p>
                            <p>Received By: <span class="font-bold text-slate-900">{{ $payment->recordedBy?->name ?? 'Accounts Admin' }}</span></p>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-bold text-slate-900 uppercase tracking-wider mb-1 text-[11px]">Terms & Conditions:</h4>
                        <p class="text-[11px] font-medium text-slate-500 leading-relaxed">
                            {{ $payment->notes ?? 'This receipt serves as official confirmation of school fees received. Fees once paid are non-refundable.' }}
                        </p>
                    </div>
                </div>

                <!-- Right Column (Totals) -->
                <div class="col-span-5 text-xs font-semibold space-y-2">
                    <div class="flex justify-between items-center py-1 text-slate-600">
                        <span>Adjusted Term Fee:</span>
                        <span class="font-mono text-slate-900 font-bold">UGX {{ number_format($payment->student?->adjustedFeeAmount($payment->term) ?? 0, 2) }}</span>
                    </div>

                    <!-- Total Paid Highlight -->
                    <div class="bg-yellow-400 text-slate-950 p-3.5 flex justify-between items-center text-xs font-extrabold uppercase shadow-2xs">
                        <span>TOTAL PAID:</span>
                        <span class="font-mono text-sm font-black">UGX {{ number_format($payment->amount, 2) }}</span>
                    </div>

                    <!-- Balance Display -->
                    <div class="border-t-2 border-slate-900 pt-2.5 flex justify-between items-center text-xs">
                        <span class="font-extrabold text-slate-900">REMAINING BALANCE:</span>
                        <span class="font-mono font-black text-rose-700 text-sm">
                            UGX {{ number_format($payment->student?->balance($payment->term) ?? 0, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 6. FOOTER -->
        <footer class="mt-12">
            <div class="h-5 bg-slate-900 w-full"></div>
            <div class="text-center text-[10px] text-slate-400 py-1.5 no-print">
                Edlink School Management System • Official Fee Receipt
            </div>
        </footer>

    </main>

</body>
</html>
