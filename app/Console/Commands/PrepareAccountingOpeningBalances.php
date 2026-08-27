<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Services\AccountingMigrationService;
use Illuminate\Console\Command;

class PrepareAccountingOpeningBalances extends Command
{
    protected $signature = 'accounting:prepare-opening {school : School id or school number} {--date= : Opening balance date} {--user= : Authorized preparer user id} {--commit : Create and submit the opening journal}';

    protected $description = 'Preview legacy finance reconciliation and optionally prepare an idempotent balanced opening journal';

    public function handle(AccountingMigrationService $service): int
    {
        $school = School::where('id', $this->argument('school'))->orWhere('school_number', $this->argument('school'))->firstOrFail();
        $date = $this->option('date') ?: now()->subDay()->toDateString();
        $preview = $service->preview($school, $date);
        $this->table(['Check', 'Legacy/operational', 'General ledger', 'Difference'], [['Fee payments', $preview['legacy_payments'], $preview['journal_payments'], $preview['payment_difference']], ['Expenses', $preview['legacy_expenses'], $preview['journal_expenses'], $preview['expense_difference']], ['Student receivables', $preview['student_operational_balance'], 'Opening draft required', '-']]);
        foreach ($preview['financial_accounts'] as $account) {
            $this->line("{$account['name']}: {$account['legacy_balance']} (ledger mapping ".($account['ledger_account_id'] ?: 'MISSING').')');
        }
        if (! $this->option('commit')) {
            $this->warn('Preview only. Re-run with --commit and --user after the balances have been reviewed and approved.');

            return self::SUCCESS;
        }
        if (! $this->option('user')) {
            $this->error('--user is required when using --commit.');

            return self::FAILURE;
        }
        $journal = $service->createOpeningDraft($school, $date, (int) $this->option('user'));
        $this->info("Opening journal {$journal->number} is {$journal->status} and awaits independent approval.");

        return self::SUCCESS;
    }
}
