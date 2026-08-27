<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountMapping;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingMigrationService
{
    public function __construct(private readonly AccountingSetupService $setup, private readonly DoubleEntryService $journals) {}

    public function preview(School $school, string $date): array
    {
        $legacyPayments = (float) FinanceLedgerEntry::where('school_id', $school->id)->where('entry_type', 'fee_payment')->whereIn('status', ['posted', 'reversed'])->whereDate('posted_at', '<=', $date)->where('direction', 'credit')->sum('amount');
        $journalPayments = (float) AccountingJournal::where('school_id', $school->id)->where('journal_type', 'fee_receipt')->whereIn('status', ['posted', 'reversed'])->whereDate('journal_date', '<=', $date)->whereHas('lines')->withSum('lines as debit_total', 'debit')->get()->sum('debit_total');
        $legacyExpenses = (float) FinanceLedgerEntry::where('school_id', $school->id)->where('entry_type', 'expense')->whereIn('status', ['posted', 'reversed'])->whereDate('posted_at', '<=', $date)->where('direction', 'debit')->sum('amount');
        $journalExpenses = (float) AccountingJournal::where('school_id', $school->id)->whereIn('journal_type', ['expense', 'supplier_bill'])->whereIn('status', ['posted', 'reversed'])->whereDate('journal_date', '<=', $date)->withSum('lines as debit_total', 'debit')->get()->sum('debit_total');
        $studentOperational = (float) Student::where('school_id', $school->id)->get()->sum(fn ($student) => $student->balance());

        return ['legacy_payments' => $legacyPayments, 'journal_payments' => $journalPayments, 'payment_difference' => $legacyPayments - $journalPayments, 'legacy_expenses' => $legacyExpenses, 'journal_expenses' => $journalExpenses, 'expense_difference' => $legacyExpenses - $journalExpenses, 'student_operational_balance' => $studentOperational, 'financial_accounts' => FinancialAccount::where('school_id', $school->id)->get()->map(fn ($account) => ['id' => $account->id, 'name' => $account->name, 'legacy_balance' => $account->balance(), 'ledger_account_id' => $account->ledger_account_id])->all()];
    }

    public function createOpeningDraft(School $school, string $date, int $userId): AccountingJournal
    {
        return DB::transaction(function () use ($school, $date, $userId) {
            $this->setup->activate($school, $userId);
            $this->setup->ensurePeriods($school, (int) substr($date, 0, 4));
            $existing = AccountingJournal::where('school_id', $school->id)->where('idempotency_key', 'opening_balance:v1')->first();
            if ($existing) {
                return $existing;
            }
            if (AccountingJournal::where('school_id', $school->id)->where('status', 'posted')->exists()) {
                throw ValidationException::withMessages(['opening_balance' => 'Opening balances can only be generated before any general-ledger journal has been posted. Use a manual adjustment journal for a later conversion.']);
            }
            $lines = [];
            $debits = 0;
            $credits = 0;
            foreach (FinancialAccount::where('school_id', $school->id)->where('is_active', true)->get() as $financial) {
                if (! $financial->ledger_account_id) {
                    throw ValidationException::withMessages(['financial_account' => 'Map every active financial account before generating opening balances.']);
                }
                $amount = round($financial->balance(), 2);
                if ($amount == 0) {
                    continue;
                }
                $lines[] = ['ledger_account_id' => $financial->ledger_account_id, 'description' => 'Opening balance: '.$financial->name, 'debit' => $amount > 0 ? $amount : 0, 'credit' => $amount < 0 ? abs($amount) : 0];
                if ($amount > 0) {
                    $debits += $amount;
                } else {
                    $credits += abs($amount);
                }
            }
            $receivables = (float) $this->preview($school, $date)['student_operational_balance'];
            if ($receivables != 0) {
                $account = $this->mapping($school->id, 'student_receivable');
                $lines[] = ['ledger_account_id' => $account->id, 'description' => 'Opening student receivables control', 'debit' => $receivables > 0 ? $receivables : 0, 'credit' => $receivables < 0 ? abs($receivables) : 0];
                if ($receivables > 0) {
                    $debits += $receivables;
                } else {
                    $credits += abs($receivables);
                }
            }
            $difference = round($debits - $credits, 2);
            if ($difference == 0 || count($lines) === 0) {
                throw ValidationException::withMessages(['opening_balance' => 'No non-zero legacy balances were found.']);
            }
            $equity = $this->mapping($school->id, 'opening_balance');
            $lines[] = ['ledger_account_id' => $equity->id, 'description' => 'Opening fund balancing amount', 'debit' => $difference < 0 ? abs($difference) : 0, 'credit' => $difference > 0 ? $difference : 0];
            $journal = $this->journals->create(['school_id' => $school->id, 'journal_date' => $date, 'reference' => 'OPENING-'.$date, 'description' => 'Controlled opening balances as at '.$date, 'journal_type' => 'opening_balance', 'currency' => strtoupper((string) SchoolSetting::getValue($school->id, 'accounting_currency', 'UGX')), 'idempotency_key' => 'opening_balance:v1'], $lines, $userId);
            $this->journals->submit($journal, $userId);

            return $journal->fresh();
        });
    }

    private function mapping(int $schoolId, string $type): LedgerAccount
    {
        $account = AccountMapping::with('account')->where('school_id', $schoolId)->where('mapping_type', $type)->whereNull('source_type')->first()?->account;
        if (! $account || ! $account->is_active || ! $account->accepts_postings) {
            throw ValidationException::withMessages(['mapping' => "Configure {$type} before migration."]);
        }

        return $account;
    }
}
