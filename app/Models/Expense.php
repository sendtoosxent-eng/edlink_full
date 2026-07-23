<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Expense extends Model
{
    public const CATEGORIES = ['Payroll', 'Utilities', 'Supplies', 'Maintenance', 'Transport', 'Meals', 'Other'];

    protected $fillable = ['school_id', 'term_id', 'category', 'amount', 'description', 'expense_date', 'reference_number', 'recorded_by'];
    protected $casts = ['amount' => 'decimal:2', 'expense_date' => 'date'];

    protected static function booted(): void
    {
        static::created(fn (self $expense) => AuditLog::record($expense->school_id, 'expense.recorded', $expense, ['amount'=>$expense->amount, 'category'=>$expense->category, 'term_id'=>$expense->term_id, 'reference_number'=>$expense->reference_number]));
        static::deleted(fn (self $expense) => AuditLog::record($expense->school_id, 'expense.deleted', $expense, ['amount'=>$expense->amount, 'category'=>$expense->category, 'term_id'=>$expense->term_id, 'reference_number'=>$expense->reference_number]));
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function poolEntry(): HasOne { return $this->hasOne(CashPoolEntry::class); }
}
