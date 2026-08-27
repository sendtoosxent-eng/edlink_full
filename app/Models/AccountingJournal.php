<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AccountingJournal extends Model
{
    protected $fillable = ['school_id', 'accounting_period_id', 'term_id', 'number', 'journal_date', 'reference', 'description', 'journal_type', 'status', 'currency', 'source_type', 'source_id', 'idempotency_key', 'reversal_of_id', 'rejection_reason', 'reversal_reason', 'evidence_path', 'created_by', 'submitted_by', 'approved_by', 'posted_by', 'rejected_by', 'submitted_at', 'approved_at', 'posted_at', 'rejected_at'];

    protected $casts = ['journal_date' => 'date', 'submitted_at' => 'datetime', 'approved_at' => 'datetime', 'posted_at' => 'datetime', 'rejected_at' => 'datetime'];

    protected static function booted(): void
    {
        static::updating(function (self $journal): void {
            if ($journal->getOriginal('status') === 'posted' && ! ($journal->status === 'reversed' && array_diff(array_keys($journal->getDirty()), ['status', 'reversal_reason', 'updated_at']) === [])) {
                throw ValidationException::withMessages(['journal' => 'Posted journals are immutable. Post a reversal instead.']);
            }
        });
        static::deleting(function (self $journal): void {
            if ($journal->status !== 'draft') {
                throw ValidationException::withMessages(['journal' => 'Submitted, posted, and reversed journals cannot be deleted.']);
            }
        });
    }

    public function lines()
    {
        return $this->hasMany(AccountingJournalLine::class);
    }

    public function period()
    {
        return $this->belongsTo(AccountingPeriod::class, 'accounting_period_id');
    }

    public function reversalOf()
    {
        return $this->belongsTo(self::class, 'reversal_of_id');
    }
}
