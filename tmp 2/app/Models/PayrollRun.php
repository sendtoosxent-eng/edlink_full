<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollRun extends Model
{
    protected $fillable = [
        'school_id', 'financial_account_id', 'evidence_path', 'term_id', 'period', 'user_id', 'payment_type',
        'salary_snapshot', 'amount', 'method', 'transaction_id',
        'bank_slip_number', 'notes', 'paid_at', 'recorded_by',
    ];

    protected $casts = [
        'salary_snapshot' => 'decimal:2',
        'amount' => 'decimal:2',
        'paid_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::created(fn (self $run) => AuditLog::record($run->school_id, 'payroll.recorded', $run, [
            'amount' => $run->amount,
            'staff_id' => $run->user_id,
            'period' => $run->period,
            'payment_type' => $run->payment_type,
            'method' => $run->method,
            'term_id' => $run->term_id,
        ]));
        static::created(fn (self $run) => app(\App\Services\FinanceLedgerService::class)->post($run, 'payroll', 'debit', 'Payroll '.$run->period.' for staff #'.$run->user_id, $run->recorded_by));
    }

    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
