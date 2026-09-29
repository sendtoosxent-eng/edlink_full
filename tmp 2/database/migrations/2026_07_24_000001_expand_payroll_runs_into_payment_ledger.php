<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'period']);
            $table->string('payment_type', 20)->default('salary')->after('user_id');
            $table->decimal('salary_snapshot', 12, 2)->default(0)->after('payment_type');
            $table->string('method', 30)->default('cash')->after('amount');
            $table->string('transaction_id', 100)->nullable()->after('method');
            $table->string('bank_slip_number', 100)->nullable()->after('transaction_id');
            $table->text('notes')->nullable()->after('bank_slip_number');
            $table->index(['school_id', 'period', 'user_id'], 'payroll_period_staff_lookup');
        });
    }

    public function down(): void
    {
        Schema::table('payroll_runs', function (Blueprint $table) {
            $table->dropIndex('payroll_period_staff_lookup');
            $table->dropColumn(['payment_type', 'salary_snapshot', 'method', 'transaction_id', 'bank_slip_number', 'notes']);
            $table->unique(['user_id', 'period']);
        });
    }
};
