<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
         * MySQL is using payroll_runs_user_id_period_unique
         * to support the foreign key on user_id.
         *
         * Create a standalone user_id index before dropping it.
         */
        if (! Schema::hasIndex(
            'payroll_runs',
            'payroll_runs_user_id_index'
        )) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->index(
                    'user_id',
                    'payroll_runs_user_id_index'
                );
            });
        }

        if (Schema::hasIndex(
            'payroll_runs',
            'payroll_runs_user_id_period_unique'
        )) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropUnique(
                    'payroll_runs_user_id_period_unique'
                );
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'payment_type')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->string('payment_type', 20)
                    ->default('salary')
                    ->after('user_id');
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'salary_snapshot')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->decimal('salary_snapshot', 12, 2)
                    ->default(0)
                    ->after('payment_type');
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'method')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->string('method', 30)
                    ->default('cash')
                    ->after('amount');
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'transaction_id')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->string('transaction_id', 100)
                    ->nullable()
                    ->after('method');
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'bank_slip_number')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->string('bank_slip_number', 100)
                    ->nullable()
                    ->after('transaction_id');
            });
        }

        if (! Schema::hasColumn('payroll_runs', 'notes')) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->text('notes')
                    ->nullable()
                    ->after('bank_slip_number');
            });
        }

        if (! Schema::hasIndex(
            'payroll_runs',
            'payroll_period_staff_lookup'
        )) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->index(
                    ['school_id', 'period', 'user_id'],
                    'payroll_period_staff_lookup'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex(
            'payroll_runs',
            'payroll_period_staff_lookup'
        )) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropIndex('payroll_period_staff_lookup');
            });
        }

        $columnsToDrop = [];

        foreach ([
            'payment_type',
            'salary_snapshot',
            'method',
            'transaction_id',
            'bank_slip_number',
            'notes',
        ] as $column) {
            if (Schema::hasColumn('payroll_runs', $column)) {
                $columnsToDrop[] = $column;
            }
        }

        if ($columnsToDrop !== []) {
            Schema::table('payroll_runs', function (Blueprint $table) use ($columnsToDrop) {
                $table->dropColumn($columnsToDrop);
            });
        }

        /*
         * Restore the original unique index first so it can support
         * the user_id foreign key before removing the temporary index.
         */
        if (! Schema::hasIndex(
            'payroll_runs',
            'payroll_runs_user_id_period_unique'
        )) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->unique(
                    ['user_id', 'period'],
                    'payroll_runs_user_id_period_unique'
                );
            });
        }

        if (Schema::hasIndex(
            'payroll_runs',
            'payroll_runs_user_id_index'
        )) {
            Schema::table('payroll_runs', function (Blueprint $table) {
                $table->dropIndex('payroll_runs_user_id_index');
            });
        }
    }
};