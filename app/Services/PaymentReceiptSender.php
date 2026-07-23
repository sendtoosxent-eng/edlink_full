<?php

namespace App\Services;

use App\Mail\PaymentReceiptMail;
use App\Models\FeePayment;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class PaymentReceiptSender
{
    public function send(int $paymentId): void
    {
        $payment = FeePayment::with(['school', 'student.guardians', 'student.schoolClass', 'term', 'recordedBy'])->find($paymentId);
        if (! $payment || ! $payment->student) return;
        if (SchoolSetting::getValue($payment->school_id, 'payment_receipt_email_enabled', 'enabled') !== 'enabled') return;

        $guardianEmails = $payment->student->guardians->pluck('email');
        $parentEmails = User::where('school_id', $payment->school_id)
            ->where('role', 'parent')
            ->whereHas('portalStudents', fn ($query) => $query->whereKey($payment->student_id))
            ->pluck('email');
        $recipients = $guardianEmails->merge($parentEmails)
            ->filter(fn ($email) => is_string($email) && filter_var(trim($email), FILTER_VALIDATE_EMAIL))
            ->map(fn ($email) => strtolower(trim($email)))
            ->unique()->values();

        foreach ($recipients as $email) {
            try {
                Mail::to($email)->send(new PaymentReceiptMail($payment));
            } catch (Throwable $exception) {
                report($exception);
                Log::warning('Payment receipt email could not be sent.', [
                    'payment_id' => $payment->id, 'recipient' => $email, 'error' => $exception->getMessage(),
                ]);
            }
        }
    }
}
