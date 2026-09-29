<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashPoolEntry extends Model
{
    protected $fillable = ['school_id', 'term_id', 'financial_account_id', 'financial_account_transfer_id', 'finance_ledger_entry_id', 'fee_payment_id', 'expense_id', 'payroll_run_id', 'direction', 'amount', 'description', 'transacted_at', 'recorded_by'];

    protected $casts = ['amount' => 'decimal:2', 'transacted_at' => 'datetime'];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function feePayment(): BelongsTo { return $this->belongsTo(FeePayment::class); }
    public function expense(): BelongsTo { return $this->belongsTo(Expense::class); }
}
