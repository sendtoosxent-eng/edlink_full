<?php

namespace App\Models;

use App\Services\FinanceLedgerService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Expense extends Model
{
    public const CATEGORIES = ['Payroll', 'Utilities', 'Supplies', 'Maintenance', 'Transport', 'Meals', 'Other'];

    protected $fillable = ['school_id', 'financial_account_id', 'supplier_id', 'expense_ledger_account_id', 'evidence_path', 'term_id', 'cost_centre_id', 'fund_id', 'settlement_type', 'category', 'payee', 'amount', 'description', 'expense_date', 'reference_number', 'recorded_by'];

    protected $casts = ['amount' => 'decimal:2', 'expense_date' => 'date'];

    protected static function booted(): void
    {
        static::created(function (self $expense): void {
            AuditLog::record($expense->school_id, 'expense.recorded', $expense, ['amount' => $expense->amount, 'category' => $expense->category, 'term_id' => $expense->term_id, 'reference_number' => $expense->reference_number]);
            app(FinanceLedgerService::class)->post($expense, 'expense', 'debit', '['.$expense->reference_number.'] '.$expense->category, $expense->recorded_by);
        });
        static::deleting(function (self $expense): void {
            $ledger = FinanceLedgerEntry::where(['source_type' => self::class, 'source_id' => $expense->id])->first();
            if ($ledger && $ledger->status !== 'pending') {
                throw ValidationException::withMessages(['expense' => 'Posted expenses cannot be deleted. Reverse the ledger entry instead.']);
            } $ledger?->delete();
        });
        static::deleted(fn (self $expense) => AuditLog::record($expense->school_id, 'expense.deleted', $expense, ['amount' => $expense->amount, 'category' => $expense->category, 'term_id' => $expense->term_id, 'reference_number' => $expense->reference_number]));
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function poolEntry(): HasOne
    {
        return $this->hasOne(CashPoolEntry::class);
    }

    public function ledgerEntry(): HasOne
    {
        return $this->hasOne(FinanceLedgerEntry::class, 'source_id')->where('source_type', self::class);
    }

    public function scopePosted(Builder $query): Builder
    {
        return $query->whereHas('ledgerEntry', fn (Builder $ledger) => $ledger->where('status', 'posted'));
    }
}
