<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class AccountingJournalLine extends Model
{
    protected $fillable = ['school_id', 'accounting_journal_id', 'ledger_account_id', 'term_id', 'cost_centre_id', 'fund_id', 'student_id', 'employee_id', 'supplier_id', 'description', 'debit', 'credit'];

    protected $casts = ['debit' => 'decimal:2', 'credit' => 'decimal:2'];

    protected static function booted(): void
    {
        static::saving(fn (self $line) => $line->assertDraft());
        static::deleting(fn (self $line) => $line->assertDraft());
    }

    private function assertDraft(): void
    {
        $journal = AccountingJournal::find($this->accounting_journal_id);
        if ($journal && $journal->status !== 'draft') {
            throw ValidationException::withMessages(['journal' => 'Lines of a submitted or posted journal are immutable.']);
        }
    }

    public function journal()
    {
        return $this->belongsTo(AccountingJournal::class, 'accounting_journal_id');
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
