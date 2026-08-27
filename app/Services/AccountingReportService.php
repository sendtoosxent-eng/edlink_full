<?php

namespace App\Services;

use App\Models\AccountingJournalLine;
use App\Models\LedgerAccount;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AccountingReportService
{
    public function movements(int $schoolId, array $filters = []): Builder
    {
        return AccountingJournalLine::query()->where('accounting_journal_lines.school_id', $schoolId)
            ->join('accounting_journals as journals', 'journals.id', '=', 'accounting_journal_lines.accounting_journal_id')
            ->where('journals.status', 'posted')
            ->when($filters['from'] ?? null, fn ($q, $date) => $q->whereDate('journals.journal_date', '>=', $date))
            ->when($filters['to'] ?? null, fn ($q, $date) => $q->whereDate('journals.journal_date', '<=', $date))
            ->when($filters['period_id'] ?? null, fn ($q, $id) => $q->where('journals.accounting_period_id', $id))
            ->when($filters['term_id'] ?? null, fn ($q, $id) => $q->where('accounting_journal_lines.term_id', $id))
            ->when($filters['cost_centre_id'] ?? null, fn ($q, $id) => $q->where('accounting_journal_lines.cost_centre_id', $id))
            ->when($filters['fund_id'] ?? null, fn ($q, $id) => $q->where('accounting_journal_lines.fund_id', $id))
            ->when($filters['student_id'] ?? null, fn ($q, $id) => $q->where('accounting_journal_lines.student_id', $id));
    }

    public function trialBalance(int $schoolId, array $filters = []): Collection
    {
        $movement = $this->movements($schoolId, $filters)
            ->selectRaw('accounting_journal_lines.ledger_account_id, SUM(accounting_journal_lines.debit) debit, SUM(accounting_journal_lines.credit) credit')
            ->groupBy('accounting_journal_lines.ledger_account_id');

        return LedgerAccount::query()->where('ledger_accounts.school_id', $schoolId)
            ->leftJoinSub($movement, 'movement', 'movement.ledger_account_id', '=', 'ledger_accounts.id')
            ->select('ledger_accounts.*')->selectRaw('COALESCE(movement.debit,0) debit, COALESCE(movement.credit,0) credit, COALESCE(movement.debit,0)-COALESCE(movement.credit,0) balance')
            ->where(function ($q) {
                $q->where('ledger_accounts.is_active', true)->orWhereNotNull('movement.ledger_account_id');
            })
            ->orderBy('ledger_accounts.code')->get();
    }

    public function summary(int $schoolId, array $filters = []): array
    {
        $rows = $this->trialBalance($schoolId, $filters);
        $net = fn (string $class) => (float) $rows->where('account_class', $class)->sum('balance');
        $income = -$net('income');
        $expenses = $net('expense');

        return [
            'assets' => $net('asset'), 'liabilities' => -$net('liability'), 'equity' => -$net('equity'),
            'income' => $income, 'expenses' => $expenses, 'surplus' => $income - $expenses,
            'debits' => (float) $rows->sum('debit'), 'credits' => (float) $rows->sum('credit'),
        ];
    }

    public function ledger(int $schoolId, int $accountId, array $filters = []): Builder
    {
        return $this->movements($schoolId, $filters)->where('accounting_journal_lines.ledger_account_id', $accountId)
            ->select('accounting_journal_lines.*', 'journals.number', 'journals.journal_date', 'journals.reference', 'journals.description as journal_description')
            ->orderBy('journals.journal_date')->orderBy('accounting_journal_lines.id');
    }

    public function receivablesByStudent(int $schoolId, array $filters = []): Collection
    {
        return $this->movements($schoolId, $filters)->join('ledger_accounts as accounts', 'accounts.id', '=', 'accounting_journal_lines.ledger_account_id')
            ->join('students', 'students.id', '=', 'accounting_journal_lines.student_id')->where('accounts.subtype', 'student_receivable')
            ->select('students.id', 'students.name')->selectRaw('SUM(accounting_journal_lines.debit-accounting_journal_lines.credit) balance')
            ->groupBy('students.id', 'students.name')->havingRaw('SUM(accounting_journal_lines.debit-accounting_journal_lines.credit) <> 0')->orderByDesc('balance')->get();
    }
}
