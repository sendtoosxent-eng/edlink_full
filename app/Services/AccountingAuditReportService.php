<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AuditLog;
use App\Models\Expense;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciation;
use App\Models\LedgerAccount;
use App\Models\School;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AccountingAuditReportService
{
    public const REPORTS = [
        'trial-balance' => 'Trial Balance', 'general-ledger' => 'General Ledger',
        'journal-register' => 'Journal Register', 'income-expenditure' => 'Income and Expenditure',
        'financial-position' => 'Statement of Financial Position', 'cashbook' => 'Cashbook',
        'receivables-aging' => 'Student Receivables', 'expense-analysis' => 'Expense Analysis',
        'chart-of-accounts' => 'Chart of Accounts', 'audit-trail' => 'Audit Trail',
        'fixed-asset-register' => 'Fixed Asset Register', 'depreciation-schedule' => 'Depreciation Schedule',
    ];

    public function __construct(private readonly AccountingReportService $reports) {}

    public function build(School $school, string $report, array $filters): array
    {
        abort_unless(isset(self::REPORTS[$report]), 404);
        $rows = match ($report) {
            'trial-balance' => $this->trialBalance($school->id, $filters),
            'general-ledger' => $this->generalLedger($school->id, $filters),
            'journal-register' => $this->journalRegister($school->id, $filters),
            'income-expenditure' => $this->incomeExpenditure($school->id, $filters),
            'financial-position' => $this->financialPosition($school->id, $filters),
            'cashbook' => $this->cashbook($school->id, $filters),
            'receivables-aging' => $this->receivables($school->id, $filters),
            'expense-analysis' => $this->expenses($school->id, $filters),
            'chart-of-accounts' => $this->chart($school->id),
            'audit-trail' => $this->auditTrail($school->id, $filters),
            'fixed-asset-register' => $this->fixedAssets($school->id),
            'depreciation-schedule' => $this->depreciationSchedule($school->id, $filters),
        };

        return [
            'key' => $report, 'title' => self::REPORTS[$report], 'school' => $school,
            'from' => $filters['from'] ?? null, 'to' => $filters['to'] ?? null,
            'generatedAt' => now(), 'columns' => $this->columns($report), 'rows' => $rows,
            'currencyColumns' => $this->currencyColumns($report),
        ];
    }

    private function trialBalance(int $schoolId, array $filters): Collection
    {
        return $this->reports->trialBalance($schoolId, $filters)->map(fn ($r) => [$r->code, $r->name, ucfirst($r->account_class), (float) $r->debit, (float) $r->credit, (float) $r->balance]);
    }

    private function generalLedger(int $schoolId, array $filters): Collection
    {
        return $this->reports->movements($schoolId, $filters)->join('ledger_accounts as accounts', 'accounts.id', '=', 'accounting_journal_lines.ledger_account_id')
            ->orderBy('accounts.code')->orderBy('journals.journal_date')->orderBy('accounting_journal_lines.id')
            ->get(['accounts.code', 'accounts.name', 'journals.journal_date', 'journals.number', 'journals.reference', 'accounting_journal_lines.description', 'accounting_journal_lines.debit', 'accounting_journal_lines.credit'])
            ->map(fn ($r) => [$r->code, $r->name, (string) $r->journal_date, $r->number, $r->reference, $r->description, (float) $r->debit, (float) $r->credit]);
    }

    private function journalRegister(int $schoolId, array $filters): Collection
    {
        return AccountingJournal::where('school_id', $schoolId)->withSum('lines', 'debit')
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('journal_date', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('journal_date', '<=', $v))
            ->orderBy('journal_date')->orderBy('id')->get()->map(fn ($r) => [$r->number, $r->journal_date->toDateString(), $r->reference, str($r->journal_type)->headline()->toString(), ucfirst($r->status), $r->description, (float) $r->lines_sum_debit, $r->posted_at?->format('Y-m-d H:i')]);
    }

    private function incomeExpenditure(int $schoolId, array $filters): Collection
    {
        $rows = $this->reports->trialBalance($schoolId, $filters)->whereIn('account_class', ['income', 'expense'])->map(fn ($r) => [$r->code, $r->name, ucfirst($r->account_class), $r->account_class === 'income' ? -(float) $r->balance : (float) $r->balance]);
        $summary = $this->reports->summary($schoolId, $filters);

        return $rows->push(['', 'Surplus / (Deficit)', 'Result', (float) $summary['surplus']]);
    }

    private function financialPosition(int $schoolId, array $filters): Collection
    {
        $rows = $this->reports->trialBalance($schoolId, $filters)->whereIn('account_class', ['asset', 'liability', 'equity'])->map(fn ($r) => [$r->code, $r->name, ucfirst($r->account_class), $r->account_class === 'asset' ? (float) $r->balance : -(float) $r->balance]);
        $summary = $this->reports->summary($schoolId, $filters);

        return $rows->push(['', 'Current period surplus / (deficit)', 'Equity', (float) $summary['surplus']]);
    }

    private function cashbook(int $schoolId, array $filters): Collection
    {
        return DB::table('cash_pool_entries as pool')->leftJoin('financial_accounts as accounts', 'accounts.id', '=', 'pool.financial_account_id')
            ->where('pool.school_id', $schoolId)->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('pool.transacted_at', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('pool.transacted_at', '<=', $v))
            ->orderBy('pool.transacted_at')->orderBy('pool.id')->get(['pool.transacted_at', 'accounts.name as account', 'pool.description', 'pool.direction', 'pool.amount'])
            ->map(fn ($r) => [substr((string) $r->transacted_at, 0, 10), $r->account, $r->description, ucfirst($r->direction), $r->direction === 'credit' ? (float) $r->amount : 0, $r->direction === 'debit' ? (float) $r->amount : 0]);
    }

    private function receivables(int $schoolId, array $filters): Collection
    {
        return $this->reports->receivablesByStudent($schoolId, $filters)->map(fn ($r) => [$r->id, $r->name, (float) $r->balance, (float) $r->balance > 0 ? 'Outstanding' : 'Credit balance']);
    }

    private function expenses(int $schoolId, array $filters): Collection
    {
        return Expense::where('school_id', $schoolId)->with(['ledgerEntry'])->whereHas('ledgerEntry', fn ($q) => $q->where('status', 'posted'))
            ->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('expense_date', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('expense_date', '<=', $v))
            ->orderBy('expense_date')->get()->map(fn ($r) => [$r->expense_date->toDateString(), $r->reference_number, $r->category, $r->payee, str($r->settlement_type)->headline()->toString(), $r->description, (float) $r->amount]);
    }

    private function chart(int $schoolId): Collection
    {
        return LedgerAccount::where('school_id', $schoolId)->orderBy('code')->get()->map(fn ($r) => [$r->code, $r->name, ucfirst($r->account_class), str($r->subtype)->headline()->toString(), ucfirst($r->normal_balance), $r->accepts_postings ? 'Yes' : 'No', $r->is_active ? 'Active' : 'Archived']);
    }

    private function auditTrail(int $schoolId, array $filters): Collection
    {
        return AuditLog::where('school_id', $schoolId)->with('user')->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('created_at', '<=', $v))
            ->latest()->get()->map(fn ($r) => [$r->created_at->format('Y-m-d H:i:s'), $r->user?->name ?: 'System', str($r->event)->headline()->toString(), class_basename($r->subject_type ?: ''), $r->subject_id, $r->ip_address, $r->metadata ? json_encode($r->metadata, JSON_UNESCAPED_SLASHES) : '']);
    }

    private function fixedAssets(int $schoolId): Collection
    {
        return FixedAsset::where('school_id', $schoolId)->with(['category', 'custodian'])->orderBy('asset_tag')->get()->map(fn ($r) => [$r->asset_tag, $r->name, $r->category->name, $r->acquisition_date->toDateString(), $r->location, $r->custodian?->name, (float) $r->cost, $r->accumulatedDepreciation(), $r->carryingValue(), str($r->status)->headline()->toString()]);
    }

    private function depreciationSchedule(int $schoolId, array $filters): Collection
    {
        return FixedAssetDepreciation::where('school_id', $schoolId)->with('asset')->when($filters['from'] ?? null, fn ($q, $v) => $q->whereDate('period_ending', '>=', $v))->when($filters['to'] ?? null, fn ($q, $v) => $q->whereDate('period_ending', '<=', $v))->orderBy('period_ending')->get()->map(fn ($r) => [$r->asset->asset_tag, $r->asset->name, $r->period_ending->toDateString(), (float) $r->opening_carrying_value, (float) $r->depreciation_amount, (float) $r->closing_carrying_value, ucfirst($r->status)]);
    }

    private function columns(string $report): array
    {
        return match ($report) {
            'trial-balance' => ['Code', 'Account', 'Class', 'Debit', 'Credit', 'Net balance'],
            'general-ledger' => ['Code', 'Account', 'Date', 'Journal', 'Reference', 'Narration', 'Debit', 'Credit'],
            'journal-register' => ['Journal', 'Date', 'Reference', 'Type', 'Status', 'Narration', 'Amount', 'Posted at'],
            'income-expenditure', 'financial-position' => ['Code', 'Account', 'Section', 'Amount'],
            'cashbook' => ['Date', 'Financial account', 'Narration', 'Direction', 'Receipts', 'Payments'],
            'receivables-aging' => ['Student ID', 'Student', 'Balance', 'Status'],
            'expense-analysis' => ['Date', 'Reference', 'Category', 'Payee', 'Settlement', 'Narration', 'Amount'],
            'chart-of-accounts' => ['Code', 'Account', 'Class', 'Subtype', 'Normal balance', 'Posting', 'Status'],
            'audit-trail' => ['Timestamp', 'User', 'Event', 'Record type', 'Record ID', 'IP address', 'Details'],
            'fixed-asset-register' => ['Asset tag', 'Asset', 'Category', 'Acquired', 'Location', 'Custodian', 'Cost', 'Accumulated depreciation', 'Carrying value', 'Status'],
            'depreciation-schedule' => ['Asset tag', 'Asset', 'Period ending', 'Opening carrying value', 'Depreciation', 'Closing carrying value', 'Status'],
        };
    }

    private function currencyColumns(string $report): array
    {
        return match ($report) {
            'trial-balance' => [3, 4, 5], 'general-ledger' => [6, 7], 'journal-register' => [6],
            'income-expenditure', 'financial-position' => [3], 'cashbook' => [4, 5],
            'receivables-aging' => [2], 'expense-analysis' => [6], 'fixed-asset-register' => [6, 7, 8], 'depreciation-schedule' => [3, 4, 5], default => [],
        };
    }
}
