<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeePayment extends Model
{
    protected $fillable = ['school_id', 'student_id', 'term_id', 'amount', 'method', 'transaction_id', 'bank_slip_number', 'notes', 'recorded_by', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $payment) => AuditLog::record($payment->school_id, 'payment.recorded', $payment, ['amount'=>$payment->amount, 'method'=>$payment->method, 'student_id'=>$payment->student_id, 'term_id'=>$payment->term_id]));
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
