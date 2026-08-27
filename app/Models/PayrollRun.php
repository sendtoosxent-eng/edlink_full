<?php

namespace App\Models;

use App\Services\FinanceLedgerService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PayrollRun extends Model
{
    protected $fillable = [
        'school_id', 'financial_account_id', 'evidence_path', 'term_id', 'period', 'user_id', 'payment_type', 'accounting_treatment',
        'salary_snapshot', 'gross_amount', 'statutory_deductions', 'other_deductions', 'amount', 'method', 'transaction_id',
        'bank_slip_number', 'notes', 'paid_at', 'recorded_by',
    ];

    protected $casts = [
        'salary_snapshot' => 'decimal:2',
        'gross_amount' => 'decimal:2', 'statutory_deductions' => 'decimal:2', 'other_deductions' => 'decimal:2',
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
        static::created(fn (self $run) => app(FinanceLedgerService::class)->post($run, 'payroll', 'debit', 'Payroll '.$run->period.' for staff #'.$run->user_id, $run->recorded_by));
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
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
