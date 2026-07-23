<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>Fee Receipt {{ $payment->id }} - {{ $school->name }}</title>
<style>
@page { size: A4 portrait; margin: 0; }
* { box-sizing: border-box; }
body { margin: 0; color: #0f172a; background: #fff; font-family: DejaVu Sans, sans-serif; font-size: 10px; }
.receipt { width: 100%; min-height: 790px; position: relative; padding-bottom: 58px; }
.header { width: 100%; border-collapse: collapse; }
.header td { padding: 0; vertical-align: middle; }
.header-stripe { width: 22px; background: #0f172a; }
.brand { height: 112px; padding: 20px 24px !important; }
.logo-cell { width: 76px; }
.logo { width: 62px; height: 62px; border: 3px solid #facc15; border-radius: 50%; padding: 3px; text-align: center; overflow: hidden; }
.logo img { width: 54px; height: 54px; object-fit: contain; border-radius: 50%; }
.logo-fallback { font-size: 27px; font-weight: bold; line-height: 54px; color: #334155; }
.school-name { margin: 0; font-size: 20px; font-weight: 800; text-transform: uppercase; line-height: 1.15; }
.motto { margin-top: 5px; color: #ca8a04; font-size: 9px; font-weight: bold; letter-spacing: 1px; text-transform: uppercase; }
.receipt-title { width: 205px; height: 112px; padding: 0 24px !important; background: #facc15; text-align: center; font-size: 21px; font-weight: 900; letter-spacing: 1px; }
.section { margin: 22px 30px 0; }
.meta { width: 100%; border: 1px solid #cbd5e1; background: #f8fafc; border-collapse: collapse; }
.meta td { padding: 18px; vertical-align: top; }
.meta .right { text-align: right; width: 42%; line-height: 1.9; }
.label { color: #94a3b8; font-size: 8px; font-weight: bold; letter-spacing: 1.4px; text-transform: uppercase; }
.student-name { margin: 5px 0 2px; font-size: 16px; font-weight: 800; }
.student-details { color: #475569; line-height: 1.7; }
.receipt-number { font-family: DejaVu Sans Mono, monospace; font-size: 12px; font-weight: bold; }
.status { display: inline-block; padding: 3px 9px; background: #d1fae5; color: #047857; font-size: 8px; font-weight: bold; text-transform: uppercase; }
.items { width: 100%; border-collapse: collapse; border: 2px solid #0f172a; }
.items th { padding: 10px; color: #fff; background: #0f172a; border-right: 1px solid #475569; font-size: 9px; text-align: left; text-transform: uppercase; }
.items th:first-child, .items td:first-child { width: 48px; text-align: center; }
.items th:last-child, .items td:last-child { width: 160px; text-align: right; }
.items td { padding: 11px 10px; border-right: 2px solid #0f172a; border-top: 2px solid #0f172a; font-weight: bold; }
.items .empty td { color: #cbd5e1; font-weight: normal; }
.words { margin-top: 12px; padding: 10px 12px; border: 1px solid #facc15; background: #fefce8; }
.words .label { color: #a16207; }
.words-text { margin-top: 4px; font-style: italic; font-weight: bold; }
.details { width: 100%; border-collapse: separate; border-spacing: 20px 0; margin: 18px -20px 0; }
.details td { vertical-align: top; }
.reference { width: 57%; }
.summary { width: 43%; }
.block-title { margin-bottom: 5px; font-size: 9px; font-weight: bold; letter-spacing: .6px; text-transform: uppercase; }
.reference-box { padding: 10px; border: 1px solid #cbd5e1; background: #f8fafc; line-height: 1.7; }
.notes { margin-top: 12px; color: #64748b; font-size: 9px; line-height: 1.45; }
.summary-line { width: 100%; border-collapse: collapse; }
.summary-line td { padding: 6px 0; }
.summary-line td:last-child { text-align: right; font-family: DejaVu Sans Mono, monospace; font-weight: bold; }
.total { width: 100%; border-collapse: collapse; background: #facc15; }
.total td { padding: 12px; font-size: 11px; font-weight: 900; text-transform: uppercase; }
.total td:last-child { text-align: right; font-family: DejaVu Sans Mono, monospace; }
.balance { width: 100%; border-collapse: collapse; border-top: 2px solid #0f172a; margin-top: 8px; }
.balance td { padding-top: 9px; font-weight: 900; }
.balance td:last-child { color: #be123c; text-align: right; font-family: DejaVu Sans Mono, monospace; font-size: 11px; }
.contact { margin: 26px 30px 0; padding-top: 12px; border-top: 1px solid #e2e8f0; color: #64748b; text-align: center; font-size: 9px; }
.footer { position: absolute; bottom: 0; width: 100%; }
.footer-dark { height: 22px; background: #0f172a; }
.footer-text { padding: 7px; color: #94a3b8; text-align: center; font-size: 8px; }
</style>
</head>
<body>
@php
    $badgePath = $school->badge_path ? public_path('storage/'.ltrim($school->badge_path, '/')) : null;
    $receiptNumber = $payment->transaction_id ?: $payment->bank_slip_number ?: 'EDL-'.str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT);
@endphp
<div class="receipt">
<table class="header"><tr>
<td class="header-stripe"></td>
<td class="brand"><table><tr><td class="logo-cell"><div class="logo">@if($badgePath && is_file($badgePath))<img src="{{ $badgePath }}">@else<div class="logo-fallback">E</div>@endif</div></td><td><h1 class="school-name">{{ $school->name }}</h1><div class="motto">{{ $school->motto ?: 'Excellence and Integrity' }}</div></td></tr></table></td>
<td class="receipt-title">FEE RECEIPT</td>
</tr></table>

<div class="section"><table class="meta"><tr><td><div class="label">Received From</div><div class="student-name">{{ $student->name }}</div><div class="student-details"><strong>{{ $student->schoolClass?->name ?: 'Class N/A' }}</strong> &bull; {{ $school->name }}<br><strong>Reg No:</strong> {{ $student->admission_no }}<br><strong>Academic Term:</strong> {{ $term?->name }}, {{ $term?->year }}</div></td><td class="right"><strong>Receipt No:</strong> <span class="receipt-number">{{ $receiptNumber }}</span><br><strong>Date Paid:</strong> {{ $payment->paid_at?->format('d M Y, h:i A') ?: now()->format('d M Y, h:i A') }}<br><strong>Payment Status:</strong> <span class="status">Paid</span></td></tr></table></div>

<div class="section"><table class="items"><thead><tr><th>No.</th><th>Fee Payment Description</th><th>Amount Paid</th></tr></thead><tbody><tr><td>01</td><td>SCHOOL FEES PAYMENT ({{ strtoupper(str_replace('_', ' ', $payment->method)) }})</td><td>UGX {{ number_format((float) $payment->amount, 2) }}</td></tr><tr class="empty"><td>02</td><td>-</td><td>0.00</td></tr><tr class="empty"><td>03</td><td>-</td><td>0.00</td></tr><tr class="empty"><td>04</td><td>-</td><td>0.00</td></tr></tbody></table>
<div class="words"><div class="label">Amount Paid in Words:</div><div class="words-text">{{ ucfirst(\Illuminate\Support\Number::spell((float) $payment->amount)) }} Ugandan Shillings Only.</div></div>
<table class="details"><tr><td class="reference"><div class="block-title">Payment Reference</div><div class="reference-box">Method: <strong>{{ strtoupper(str_replace('_', ' ', $payment->method)) }}</strong><br>Ref / Slip ID: <strong>{{ $payment->transaction_id ?: $payment->bank_slip_number ?: 'N/A' }}</strong><br>Received By: <strong>{{ $payment->recordedBy?->name ?: 'Accounts Admin' }}</strong></div><div class="notes"><div class="block-title">Terms & Conditions</div>{{ $payment->notes ?: 'This receipt serves as official confirmation of school fees received. Fees once paid are non-refundable.' }}</div></td><td class="summary"><table class="summary-line"><tr><td>Expected Term Fee:</td><td>UGX {{ number_format((float) ($student->mappedFeeAmount($term) ?? 0), 2) }}</td></tr></table><table class="total"><tr><td>Total Paid:</td><td>UGX {{ number_format((float) $payment->amount, 2) }}</td></tr></table><table class="balance"><tr><td>Remaining Balance:</td><td>UGX {{ number_format(max(0, (float) $student->balance($term)), 2) }}</td></tr></table></td></tr></table></div>

<div class="contact">{{ collect([$school->address, $school->phone, $school->email, $school->website])->filter()->join('  |  ') }}</div>
<div class="footer"><div class="footer-dark"></div><div class="footer-text">Edlink School Management System &bull; Official Fee Receipt</div></div>
</div>
</body></html>