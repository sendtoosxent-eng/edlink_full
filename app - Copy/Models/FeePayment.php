<?php

namespace App\Models;

use App\Services\PaymentReceiptSender;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class FeePayment extends Model
{
    protected $fillable = ['school_id', 'financial_account_id', 'evidence_path', 'student_id', 'term_id', 'amount', 'method', 'transaction_id', 'bank_slip_number', 'notes', 'recorded_by', 'paid_at'];

    protected $casts = [
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(function (self $payment): void {
            app(\App\Services\FinanceLedgerService::class)->post($payment, 'fee_payment', 'credit', 'Student fee payment: '.($payment->student?->name ?? 'student'), $payment->recorded_by);
            AuditLog::record($payment->school_id, 'payment.recorded', $payment, ['amount'=>$payment->amount,'method'=>$payment->method,'student_id'=>$payment->student_id,'term_id'=>$payment->term_id]);
        });
        static::deleting(function (self $payment): void {
            $ledger = FinanceLedgerEntry::where(['source_type'=>self::class,'source_id'=>$payment->id])->first();
            if ($ledger && $ledger->status !== 'pending') throw \Illuminate\Validation\ValidationException::withMessages(['payment'=>'Posted payments cannot be deleted. Reverse the ledger entry instead.']);
            $ledger?->delete();
        });
        static::deleted(fn (self $payment) => AuditLog::record($payment->school_id, 'payment.deleted', $payment, ['amount'=>$payment->amount,'student_id'=>$payment->student_id,'term_id'=>$payment->term_id]));
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
