<!DOCTYPE html>
<html><body style="margin:0;background:#f3f4f6;font-family:Arial,sans-serif;color:#252641">
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="padding:28px 12px"><tr><td align="center">
<table role="presentation" width="620" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border-radius:16px;overflow:hidden">
<tr><td style="background:#252641;padding:26px 32px"><h1 style="margin:0;color:#facc15;font-size:22px">Payment received</h1><p style="margin:6px 0 0;color:#d1d5db;font-size:14px">{{ $school->name }}</p></td></tr>
<tr><td style="padding:32px">
<p style="margin-top:0;font-size:16px">Dear Parent/Guardian,</p>
<p style="font-size:15px;line-height:1.7">Thank you. We have received a school fees payment for <strong>{{ $student->name }}</strong>.</p>
<table role="presentation" width="100%" cellspacing="0" cellpadding="8" style="margin:22px 0;background:#f8fafc;border:1px solid #e5e7eb;border-radius:10px;font-size:14px">
<tr><td>Amount received</td><td align="right"><strong>UGX {{ number_format((float) $payment->amount, 0) }}</strong></td></tr>
<tr><td>Payment date</td><td align="right">{{ $payment->paid_at?->format('d M Y, h:i A') }}</td></tr>
<tr><td>Payment method</td><td align="right">{{ ucfirst(str_replace('_', ' ', $payment->method)) }}</td></tr>
<tr><td>Receipt/reference</td><td align="right">{{ $payment->transaction_id ?: $payment->bank_slip_number ?: 'EDL-'.str_pad($payment->id, 6, '0', STR_PAD_LEFT) }}</td></tr>
<tr><td>Term</td><td align="right">{{ $payment->term?->name }}, {{ $payment->term?->year }}</td></tr>
</table>
<p style="font-size:14px;line-height:1.7">Your official PDF receipt is attached to this email. Please keep it for your records.</p>
<p style="margin-bottom:0;font-size:14px">Thank you for supporting your learner and {{ $school->name }}.</p>
</td></tr>
<tr><td style="padding:18px 32px;background:#f8fafc;color:#64748b;font-size:12px">{{ collect([$school->phone, $school->email, $school->website])->filter()->join(' · ') }}</td></tr>
</table></td></tr></table></body></html>
