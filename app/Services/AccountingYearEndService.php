<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountMapping;
use App\Models\FiscalYear;
use App\Models\SchoolSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingYearEndService
{
    public function __construct(private readonly AccountingReportService $reports, private readonly DoubleEntryService $journals) {}

    public function prepareClosingJournal(FiscalYear $year, int $userId): AccountingJournal
    {
        return DB::transaction(function () use ($year, $userId) {
            $year = FiscalYear::where('school_id', $year->school_id)->lockForUpdate()->findOrFail($year->id);
            if ($year->status !== 'open') {
                throw ValidationException::withMessages(['fiscal_year' => 'Only an open financial year can be closed.']);
            }
            $existing = AccountingJournal::where('school_id', $year->school_id)->where('idempotency_key', 'year_end:'.$year->id)->first();
            if ($existing) {
                return $existing;
            }
            $rows = $this->reports->trialBalance($year->school_id, ['from' => $year->starts_on->toDateString(), 'to' => $year->ends_on->toDateString()])->whereIn('account_class', ['income', 'expense'])->filter(fn ($row) => (float) $row->balance !== 0.0);
            if ($rows->isEmpty()) {
                throw ValidationException::withMessages(['fiscal_year' => 'There are no posted income or expense balances to close.']);
            }
            $lines = [];
            $net = 0;
            foreach ($rows as $row) {
                $balance = round((float) $row->balance, 2);
                $net += $balance;
                $lines[] = ['ledger_account_id' => $row->id, 'description' => 'Close '.$row->name, 'debit' => $balance < 0 ? abs($balance) : 0, 'credit' => $balance > 0 ? $balance : 0];
            }
            $retained = AccountMapping::with('account')->where('school_id', $year->school_id)->where('mapping_type', 'retained_surplus')->whereNull('source_type')->first()?->account;
            if (! $retained || ! $retained->is_active || ! $retained->accepts_postings) {
                throw ValidationException::withMessages(['mapping' => 'Configure the retained surplus account before year-end.']);
            }
            $lines[] = ['ledger_account_id' => $retained->id, 'description' => 'Transfer annual surplus or deficit', 'debit' => $net > 0 ? $net : 0, 'credit' => $net < 0 ? abs($net) : 0];
            $journal = $this->journals->create(['school_id' => $year->school_id, 'journal_date' => $year->ends_on->toDateString(), 'reference' => 'YEAR-END-'.$year->name, 'description' => 'Close income and expenditure for '.$year->name, 'journal_type' => 'year_end', 'currency' => strtoupper((string) SchoolSetting::getValue($year->school_id, 'accounting_currency', 'UGX')), 'idempotency_key' => 'year_end:'.$year->id], $lines, $userId);
            $this->journals->submit($journal, $userId);

            return $journal->fresh();
        });
    }

    public function finalize(FiscalYear $year, int $userId): void
    {
        DB::transaction(function () use ($year, $userId) {
            $year = FiscalYear::where('school_id', $year->school_id)->lockForUpdate()->findOrFail($year->id);
            $journal = AccountingJournal::where('school_id', $year->school_id)->where('idempotency_key', 'year_end:'.$year->id)->first();
            if (! $journal || $journal->status !== 'posted') {
                throw ValidationException::withMessages(['fiscal_year' => 'Post the approved year-end journal before finalizing the year.']);
            }$year->periods()->where('status', '!=', 'locked')->update(['status' => 'locked', 'status_reason' => 'Financial year finalized', 'status_changed_by' => $userId, 'status_changed_at' => now()]);
            $year->update(['status' => 'closed', 'closed_at' => now(), 'closed_by' => $userId]);
        });
    }
}
