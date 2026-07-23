<?php

namespace App\Models;

use App\Services\PaymentReceiptSender;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FeePayment extends Model
{
    protected $fillable = ['school_id', 'student_id', 'term_id', 'amount', 'method', 'transaction_id', 'bank_slip_number', 'notes', 'recorded_by', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $payment): void {
            AuditLog::record($payment->school_id, 'payment.recorded', $payment, ['amount' => $payment->amount, 'method' => $payment->method, 'student_id' => $payment->student_id, 'term_id' => $payment->term_id]);

            $student = $payment->student;
            $portalUserIds = DB::table('portal_user_students')->where('student_id', $payment->student_id)->pluck('user_id');
            $guardianEmails = $student?->guardians()->whereNotNull('email')->pluck('email') ?? collect();
            $guardianUserIds = User::where('school_id', $payment->school_id)->whereIn('email', $guardianEmails)->pluck('id');

            foreach ($portalUserIds->merge($guardianUserIds)->unique() as $userId) {
                DB::table('school_notifications')->insert([
                    'school_id' => $payment->school_id,
                    'user_id' => $userId,
                    'title' => 'Payment received',
                    'message' => 'UGX '.number_format((float) $payment->amount, 0).' was recorded for '.($student?->name ?? 'your learner').'.' ,
                    'type' => 'success',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
            $paymentId = $payment->id;
            DB::afterCommit(fn () => app(PaymentReceiptSender::class)->send($paymentId));
        });
        static::deleted(fn (self $payment) => AuditLog::record($payment->school_id, 'payment.deleted', $payment, ['amount'=>$payment->amount, 'student_id'=>$payment->student_id, 'term_id'=>$payment->term_id]));
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
