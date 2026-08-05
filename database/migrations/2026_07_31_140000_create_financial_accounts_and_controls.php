<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Financial accounts
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('financial_accounts')) {
            Schema::create('financial_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->string('name');
                $table->string('type');
                $table->string('currency', 3)->default('UGX');
                $table->decimal('opening_balance', 14, 2)->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();

                $table->unique(
                    ['school_id', 'name'],
                    'financial_accounts_school_id_name_unique'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Financial account transfers
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasTable('financial_account_transfers')) {
            Schema::create('financial_account_transfers', function (Blueprint $table) {
                $table->id();

                $table->foreignId('school_id')
                    ->constrained()
                    ->cascadeOnDelete();

                $table->foreignId('from_account_id')
                    ->constrained('financial_accounts')
                    ->restrictOnDelete();

                $table->foreignId('to_account_id')
                    ->constrained('financial_accounts')
                    ->restrictOnDelete();

                $table->decimal('amount', 14, 2);
                $table->date('transfer_date');
                $table->string('reference')->nullable();
                $table->text('notes')->nullable();
                $table->string('evidence_path')->nullable();
                $table->string('status')->default('pending');

                $table->foreignId('recorded_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->foreignId('approved_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();

                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Fee payments, expenses and payroll
        |--------------------------------------------------------------------------
        */

        foreach (['fee_payments', 'expenses', 'payroll_runs'] as $tableName) {
            if (! Schema::hasColumn($tableName, 'financial_account_id')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->foreignId('financial_account_id')
                        ->nullable()
                        ->constrained('financial_accounts')
                        ->restrictOnDelete();
                });
            }

            if (! Schema::hasColumn($tableName, 'evidence_path')) {
                Schema::table($tableName, function (Blueprint $table) {
                    $table->string('evidence_path')->nullable();
                });
            }
        }

        /*
        |--------------------------------------------------------------------------
        | Finance ledger entries
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasColumn(
            'finance_ledger_entries',
            'financial_account_id'
        )) {
            Schema::table('finance_ledger_entries', function (Blueprint $table) {
                $table->foreignId('financial_account_id')
                    ->nullable()
                    ->constrained('financial_accounts')
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Cash pool entries
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasColumn(
            'cash_pool_entries',
            'financial_account_id'
        )) {
            Schema::table('cash_pool_entries', function (Blueprint $table) {
                $table->foreignId('financial_account_id')
                    ->nullable()
                    ->constrained('financial_accounts')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn(
            'cash_pool_entries',
            'financial_account_transfer_id'
        )) {
            Schema::table('cash_pool_entries', function (Blueprint $table) {
                $table->foreignId('financial_account_transfer_id')
                    ->nullable()
                    ->constrained('financial_account_transfers')
                    ->restrictOnDelete();
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Finance reconciliations
        |--------------------------------------------------------------------------
        |
        | MySQL was using the existing unique index to support the school_id
        | foreign key. Create a dedicated school_id index before removing it.
        |
        */

        if (! Schema::hasIndex(
            'finance_reconciliations',
            'finance_reconciliations_school_id_index'
        )) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->index(
                    'school_id',
                    'finance_reconciliations_school_id_index'
                );
            });
        }

        if (Schema::hasIndex(
            'finance_reconciliations',
            'finance_reconciliations_school_id_period_ending_unique'
        )) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->dropUnique(
                    'finance_reconciliations_school_id_period_ending_unique'
                );
            });
        }

        if (! Schema::hasColumn(
            'finance_reconciliations',
            'financial_account_id'
        )) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->foreignId('financial_account_id')
                    ->nullable()
                    ->constrained('financial_accounts')
                    ->restrictOnDelete();
            });
        }

        if (! Schema::hasColumn('finance_reconciliations', 'status')) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->string('status')
                    ->default('closed');
            });
        }

        if (! Schema::hasColumn('finance_reconciliations', 'closed_at')) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->timestamp('closed_at')->nullable();
            });
        }

        if (! Schema::hasColumn('finance_reconciliations', 'reopened_by')) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->foreignId('reopened_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasColumn('finance_reconciliations', 'reopen_reason')) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->text('reopen_reason')->nullable();
            });
        }

        if (! Schema::hasColumn('finance_reconciliations', 'reopened_at')) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->timestamp('reopened_at')->nullable();
            });
        }

        if (! Schema::hasIndex(
            'finance_reconciliations',
            'finance_reconciliation_account_period_unique'
        )) {
            Schema::table('finance_reconciliations', function (Blueprint $table) {
                $table->unique(
                    [
                        'school_id',
                        'financial_account_id',
                        'period_ending',
                    ],
                    'finance_reconciliation_account_period_unique'
                );
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Create default accounts and assign financial history
        |--------------------------------------------------------------------------
        */

        DB::table('schools')
            ->orderBy('id')
            ->chunkById(100, function ($schools): void {
                foreach ($schools as $school) {
                    $accountIds = [];

                    $defaultAccounts = [
                        ['Cash on Hand', 'cash'],
                        ['Main Bank Account', 'bank'],
                        ['Mobile Money', 'mobile_money'],
                        ['Petty Cash', 'petty_cash'],
                    ];

                    foreach ($defaultAccounts as [$name, $type]) {
                        $accountId = DB::table('financial_accounts')
                            ->where('school_id', $school->id)
                            ->where('name', $name)
                            ->value('id');

                        if (! $accountId) {
                            $accountId = DB::table('financial_accounts')
                                ->insertGetId([
                                    'school_id' => $school->id,
                                    'name' => $name,
                                    'type' => $type,
                                    'currency' => 'UGX',
                                    'opening_balance' => 0,
                                    'is_active' => true,
                                    'created_at' => now(),
                                    'updated_at' => now(),
                                ]);
                        }

                        $accountIds[$type] = $accountId;
                    }

                    /*
                     * Fee payments
                     */
                    DB::table('fee_payments')
                        ->where('school_id', $school->id)
                        ->whereNull('financial_account_id')
                        ->orderBy('id')
                        ->chunkById(500, function ($payments) use ($accountIds): void {
                            foreach ($payments as $payment) {
                                $accountId =
                                    $accountIds[$payment->method]
                                    ?? $accountIds['cash'];

                                DB::table('fee_payments')
                                    ->where('id', $payment->id)
                                    ->update([
                                        'financial_account_id' => $accountId,
                                    ]);
                            }
                        });

                    /*
                     * Expenses
                     */
                    DB::table('expenses')
                        ->where('school_id', $school->id)
                        ->whereNull('financial_account_id')
                        ->update([
                            'financial_account_id' => $accountIds['cash'],
                        ]);

                    /*
                     * Payroll
                     */
                    DB::table('payroll_runs')
                        ->where('school_id', $school->id)
                        ->whereNull('financial_account_id')
                        ->orderBy('id')
                        ->chunkById(500, function ($payrollRuns) use ($accountIds): void {
                            foreach ($payrollRuns as $payrollRun) {
                                $accountId =
                                    $accountIds[$payrollRun->method]
                                    ?? $accountIds['cash'];

                                DB::table('payroll_runs')
                                    ->where('id', $payrollRun->id)
                                    ->update([
                                        'financial_account_id' => $accountId,
                                    ]);
                            }
                        });

                    /*
                     * Cash pool entries
                     */
                    DB::table('cash_pool_entries')
                        ->where('school_id', $school->id)
                        ->whereNull('financial_account_id')
                        ->orderBy('id')
                        ->chunkById(500, function ($poolEntries) use ($accountIds): void {
                            foreach ($poolEntries as $poolEntry) {
                                $accountType = 'cash';

                                if ($poolEntry->fee_payment_id) {
                                    $method = DB::table('fee_payments')
                                        ->where('id', $poolEntry->fee_payment_id)
                                        ->value('method');

                                    $accountType = $method ?: 'cash';
                                } elseif ($poolEntry->payroll_run_id) {
                                    $method = DB::table('payroll_runs')
                                        ->where('id', $poolEntry->payroll_run_id)
                                        ->value('method');

                                    $accountType = $method ?: 'cash';
                                }

                                DB::table('cash_pool_entries')
                                    ->where('id', $poolEntry->id)
                                    ->update([
                                        'financial_account_id' =>
                                            $accountIds[$accountType]
                                            ?? $accountIds['cash'],
                                    ]);
                            }
                        });

                    /*
                     * Finance ledger entries
                     */
                    DB::table('finance_ledger_entries')
                        ->where('school_id', $school->id)
                        ->whereNull('financial_account_id')
                        ->orderBy('id')
                        ->chunkById(500, function ($ledgerEntries) use ($accountIds): void {
                            foreach ($ledgerEntries as $ledgerEntry) {
                                $accountId = DB::table('cash_pool_entries')
                                    ->where(
                                        'finance_ledger_entry_id',
                                        $ledgerEntry->id
                                    )
                                    ->value('financial_account_id');

                                DB::table('finance_ledger_entries')
                                    ->where('id', $ledgerEntry->id)
                                    ->update([
                                        'financial_account_id' =>
                                            $accountId
                                            ?? $accountIds['cash'],
                                    ]);
                            }
                        });

                    /*
                     * Finance reconciliations
                     */
                    DB::table('finance_reconciliations')
                        ->where('school_id', $school->id)
                        ->whereNull('financial_account_id')
                        ->update([
                            'financial_account_id' => $accountIds['cash'],
                            'status' => 'closed',
                            'closed_at' => now(),
                        ]);
                }
            });
    }

    public function down(): void
    {
        throw new RuntimeException(
            'Financial account migration is intentionally irreversible after financial history is assigned.'
        );
    }
};