<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->foreignId('supplier_id')->nullable()->after('financial_account_id')->constrained('accounting_suppliers')->restrictOnDelete();
            $table->foreignId('expense_ledger_account_id')->nullable()->after('supplier_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->after('term_id')->constrained('cost_centres')->restrictOnDelete();
            $table->foreignId('fund_id')->nullable()->after('cost_centre_id')->constrained('accounting_funds')->restrictOnDelete();
            $table->string('settlement_type', 20)->default('immediate')->after('fund_id');
            $table->string('payee')->nullable()->after('category');
            $table->index(['school_id', 'supplier_id', 'expense_date']);
        });
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->decimal('gross_amount', 18, 2)->nullable()->after('salary_snapshot');
            $table->decimal('statutory_deductions', 18, 2)->default(0)->after('gross_amount');
            $table->decimal('other_deductions', 18, 2)->default(0)->after('statutory_deductions');
            $table->string('accounting_treatment', 20)->default('direct')->after('payment_type');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', fn (Blueprint $table) => $table->dropColumn(['gross_amount', 'statutory_deductions', 'other_deductions', 'accounting_treatment']));
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
            $table->dropForeign(['expense_ledger_account_id']);
            $table->dropForeign(['cost_centre_id']);
            $table->dropForeign(['fund_id']);
            $table->dropIndex(['school_id', 'supplier_id', 'expense_date']);
            $table->dropColumn(['supplier_id', 'expense_ledger_account_id', 'cost_centre_id', 'fund_id', 'settlement_type', 'payee']);
        });
    }
};
