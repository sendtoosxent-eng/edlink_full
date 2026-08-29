<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountingJournalLine;
use App\Models\AccountMapping;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\FinancialAccountTransfer;
use App\Models\LedgerAccount;
use App\Models\PayrollRun;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Support\AccountingMoney;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AccountingPostingService
{
    public function __construct(private readonly AccountingSetupService $setup, private readonly DoubleEntryService $journals) {}

    public function postApprovedLedgerEntry(FinanceLedgerEntry $entry, int $approverId): AccountingJournal
    {
        $source = $entry->source_type && class_exists($entry->source_type) ? $entry->source_type::find($entry->source_id) : null;
        if (! $source instanceof Model || (int) $source->school_id !== (int) $entry->school_id) {
            $this->fail('accounting', 'The approved transaction source is missing or belongs to another school.');
        }

        return match ($entry->source_type) {
            FeePayment::class => $this->postFeePayment($source, $approverId),
            Expense::class => $this->postExpense($source, $approverId),
            PayrollRun::class => $this->postPayrollPayment($source, $approverId),
            default => $this->fail('accounting', 'No double-entry posting rule is configured for this transaction type.'),
        };
    }

    public function supports(FinanceLedgerEntry $entry): bool
    {
        return in_array($entry->source_type, [FeePayment::class, Expense::class, PayrollRun::class], true);
    }

    public function postFeePayment(FeePayment $payment, int $approverId): AccountingJournal
    {
        $cash = $this->financialAccount($payment->school_id, $payment->financial_account_id);
        $receivable = $this->mapping($payment->school_id, 'student_receivable', 'asset');

        $paymentMinor = AccountingMoney::minor((string) $payment->amount);
        $outstandingMinor = max(0, $this->receivableBalanceMinor($payment->school_id, $payment->student_id, $payment->term_id, $receivable->id));
        $appliedMinor = min($paymentMinor, $outstandingMinor);
        $advanceMinor = $paymentMinor - $appliedMinor;
        $lines = [
            $this->line($cash->ledger_account_id, $payment->amount, 0, $payment->term_id, $payment->student_id, 'Receipt into '.$cash->name),
        ];
        if ($appliedMinor > 0) {
            $lines[] = $this->line($receivable->id, 0, AccountingMoney::decimal($appliedMinor), $payment->term_id, $payment->student_id, 'Student fee receivable settled');
        }
        if ($advanceMinor > 0) {
            $advance = $this->mapping($payment->school_id, 'fees_received_in_advance', 'liability');
            $lines[] = $this->line($advance->id, 0, AccountingMoney::decimal($advanceMinor), $payment->term_id, $payment->student_id, 'Unapplied fee received in advance');
        }

        return $this->postSource($payment, 'fee_receipt', $payment->paid_at?->toDateString() ?? now()->toDateString(), 'Fee payment for student #'.$payment->student_id, $lines, $payment->recorded_by, $approverId);
    }

    public function postExpense(Expense $expense, int $approverId): AccountingJournal
    {
        $debit = $expense->expense_ledger_account_id
            ? $this->validAccount(LedgerAccount::where('school_id', $expense->school_id)->find($expense->expense_ledger_account_id), $expense->school_id, null, 'The selected expense account is unavailable.')
            : ($this->mappingOrNull($expense->school_id, 'expense_category:'.str($expense->category)->slug(), 'expense')
            ?? $this->sourceMapping($expense->school_id, 'expense_category', Expense::class, $expense->category)
            ?? $this->mapping($expense->school_id, 'default_expense', 'expense'));
        $credit = $expense->settlement_type === 'credit'
            ? $this->mapping($expense->school_id, 'supplier_payable', 'liability')
            : $this->financialAccount($expense->school_id, $expense->financial_account_id)->ledgerAccount;

        return $this->postSource($expense, $expense->settlement_type === 'credit' ? 'supplier_bill' : 'expense', $expense->expense_date->toDateString(), $expense->description ?: $expense->category, [
            $this->line($debit->id, $expense->amount, 0, $expense->term_id, null, $expense->category) + ['cost_centre_id' => $expense->cost_centre_id, 'fund_id' => $expense->fund_id, 'supplier_id' => $expense->supplier_id],
            $this->line($credit->id, 0, $expense->amount, $expense->term_id, null, $expense->settlement_type === 'credit' ? 'Supplier payable' : 'Immediate payment') + ['cost_centre_id' => $expense->cost_centre_id, 'fund_id' => $expense->fund_id, 'supplier_id' => $expense->supplier_id],
        ], $expense->recorded_by, $approverId);
    }

    public function postPayrollPayment(PayrollRun $run, int $approverId): AccountingJournal
    {
        $cash = $this->financialAccount($run->school_id, $run->financial_account_id);
        if ($run->payment_type === 'advance') {
            $advance = $this->mapping($run->school_id, 'staff_advance', 'asset');

            return $this->postSource($run, 'payroll_advance', $run->paid_at?->toDateString() ?? now()->toDateString(), 'Staff advance for employee #'.$run->user_id, [
                $this->line($advance->id, $run->amount, 0, $run->term_id, null, 'Staff advance', $run->user_id),
                $this->line($cash->ledger_account_id, 0, $run->amount, $run->term_id, null, 'Paid from '.$cash->name, $run->user_id),
            ], $run->recorded_by, $approverId);
        }
        $staff = $run->staff;
        $isTeaching = $staff && ($staff->role === 'teacher' || str_contains(strtolower((string) $staff->job_title), 'teach'));
        $salaryExpense = $this->mapping($run->school_id, $isTeaching ? 'teaching_salary_expense' : 'non_teaching_salary_expense', 'expense');
        $salaryPayable = $this->mapping($run->school_id, 'salaries_payable', 'liability');
        $this->postSource($run, 'payroll_accrual', $run->paid_at?->toDateString() ?? now()->toDateString(), 'Payroll accrual for employee #'.$run->user_id, [
            $this->line($salaryExpense->id, $run->amount, 0, $run->term_id, null, 'Salary expense', $run->user_id),
            $this->line($salaryPayable->id, 0, $run->amount, $run->term_id, null, 'Salary payable', $run->user_id),
        ], $run->recorded_by, $approverId);

        return $this->postSource($run, 'payroll_payment', $run->paid_at?->toDateString() ?? now()->toDateString(), 'Payroll payment for employee #'.$run->user_id, [
            $this->line($salaryPayable->id, $run->amount, 0, $run->term_id, null, 'Settle salary payable', $run->user_id),
            $this->line($cash->ledger_account_id, 0, $run->amount, $run->term_id, null, 'Paid from '.$cash->name, $run->user_id),
        ], $run->recorded_by, $approverId);
    }

    public function postTransfer(FinancialAccountTransfer $transfer, int $approverId): AccountingJournal
    {
        $from = $this->financialAccount($transfer->school_id, $transfer->from_account_id);
        $to = $this->financialAccount($transfer->school_id, $transfer->to_account_id);

        return $this->postSource($transfer, 'account_transfer', $transfer->transfer_date->toDateString(), 'Transfer '.($transfer->reference ?: '#'.$transfer->id), [
            $this->line($to->ledger_account_id, $transfer->amount, 0, null, null, 'Transfer to '.$to->name),
            $this->line($from->ledger_account_id, 0, $transfer->amount, null, null, 'Transfer from '.$from->name),
        ], $transfer->recorded_by, $approverId);
    }

    public function reverseForLegacyEntry(FinanceLedgerEntry $entry, string $reason, int $userId): ?AccountingJournal
    {
        $result = null;
        $originals = AccountingJournal::where('school_id', $entry->school_id)->where('source_type', $entry->source_type)->where('source_id', $entry->source_id)->where('status', 'posted')->orderBy('id')->get();
        foreach ($originals as $original) {
            $result = $this->journals->reverse($original, $reason, $userId);
        }

        return $result;
    }

    private function postSource(Model $source, string $type, string $date, string $description, array $lines, int $preparedBy, int $approvedBy): AccountingJournal
    {
        return DB::transaction(function () use ($source, $type, $date, $description, $lines, $preparedBy, $approvedBy) {
            $key = $type.':'.$source::class.':'.$source->getKey();
            $existing = AccountingJournal::where('school_id', $source->school_id)->where('idempotency_key', $key)->lockForUpdate()->first();
            if ($existing) {
                if (! in_array($existing->status, ['posted', 'reversed'], true)) {
                    $this->fail('accounting', 'A non-posted journal already exists for this transaction. Resolve it in Accounting.');
                }

                return $existing;
            }
            $school = School::findOrFail($source->school_id);
            $this->setup->activate($school, $preparedBy);
            $this->setup->ensurePeriods($school, (int) substr($date, 0, 4));

            return $this->journals->createAndPost([
                'school_id' => $source->school_id, 'term_id' => $source->term_id ?? null, 'journal_date' => $date,
                'reference' => $type.'-'.$source->getKey(), 'description' => $description, 'journal_type' => $type,
                'currency' => $this->currency($source->school_id), 'source_type' => $source::class,
                'source_id' => $source->getKey(), 'idempotency_key' => $key,
            ], $lines, $preparedBy, $approvedBy);
        });
    }

    private function mapping(int $schoolId, string $type, ?string $class = null): LedgerAccount
    {
        $mapping = AccountMapping::with('account')->where('school_id', $schoolId)->where('mapping_type', $type)->whereNull('source_type')->whereNull('source_id')->first();

        return $this->validAccount($mapping?->account, $schoolId, $class, "Configure the '{$type}' posting rule in Accounting Settings.");
    }

    private function sourceMapping(int $schoolId, string $type, string $sourceType, string|int $sourceId): ?LedgerAccount
    {
        $mapping = AccountMapping::with('account')->where('school_id', $schoolId)->where('mapping_type', $type)->where('source_type', $sourceType)->where('source_id', (string) $sourceId)->first();

        return $mapping ? $this->validAccount($mapping->account, $schoolId, null, 'The configured source posting account is unavailable.') : null;
    }

    private function mappingOrNull(int $schoolId, string $type, ?string $class = null): ?LedgerAccount
    {
        $mapping = AccountMapping::with('account')->where('school_id', $schoolId)->where('mapping_type', $type)->whereNull('source_type')->whereNull('source_id')->first();

        return $mapping ? $this->validAccount($mapping->account, $schoolId, $class, "The '{$type}' posting rule is unavailable.") : null;
    }

    private function financialAccount(int $schoolId, ?int $id): FinancialAccount
    {
        $account = FinancialAccount::with('ledgerAccount')->where('school_id', $schoolId)->find($id);
        if (! $account || ! $account->is_active || ! $account->ledgerAccount) {
            $this->fail('financial_account_id', 'Select an active financial account mapped in Accounting Settings.');
        }
        $this->validAccount($account->ledgerAccount, $schoolId, 'asset', 'This financial account is not mapped to an active cash or bank ledger account.');

        return $account;
    }

    private function accountByCode(int $schoolId, string $code, ?string $class = null): LedgerAccount
    {
        return $this->validAccount(LedgerAccount::where('school_id', $schoolId)->where('code', $code)->first(), $schoolId, $class, "Ledger account {$code} is unavailable.");
    }

    private function receivableBalanceMinor(int $schoolId, int $studentId, ?int $termId, int $accountId): int
    {
        $row = AccountingJournalLine::query()->join('accounting_journals as journals', 'journals.id', '=', 'accounting_journal_lines.accounting_journal_id')
            ->where('accounting_journal_lines.school_id', $schoolId)->where('journals.status', 'posted')
            ->where('accounting_journal_lines.ledger_account_id', $accountId)->where('accounting_journal_lines.student_id', $studentId)
            ->when($termId, fn ($query) => $query->where('accounting_journal_lines.term_id', $termId))
            ->selectRaw('COALESCE(SUM(accounting_journal_lines.debit-accounting_journal_lines.credit),0) balance')->first();

        return AccountingMoney::minor((string) $row->balance);
    }

    private function validAccount(?LedgerAccount $account, int $schoolId, ?string $class, string $message): LedgerAccount
    {
        if (! $account || (int) $account->school_id !== $schoolId || ! $account->is_active || ! $account->accepts_postings || ($class && $account->account_class !== $class) || $account->currency !== $this->currency($schoolId)) {
            $this->fail('account_mapping', $message);
        }

        return $account;
    }

    private function line(int $account, mixed $debit, mixed $credit, ?int $term, ?int $student, ?string $description, ?int $employee = null): array
    {
        return ['ledger_account_id' => $account, 'term_id' => $term, 'student_id' => $student, 'employee_id' => $employee, 'description' => $description, 'debit' => (string) $debit, 'credit' => (string) $credit];
    }

    private function currency(int $schoolId): string
    {
        return strtoupper((string) SchoolSetting::getValue($schoolId, 'accounting_currency', SchoolSetting::getValue($schoolId, 'currency', 'UGX')));
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
