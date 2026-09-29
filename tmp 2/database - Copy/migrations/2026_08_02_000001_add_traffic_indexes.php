<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fee_payments', fn (Blueprint $table) => $table->index(['school_id', 'term_id', 'paid_at'], 'fee_payments_school_term_paid_idx'));
        Schema::table('expenses', fn (Blueprint $table) => $table->index(['school_id', 'term_id', 'expense_date'], 'expenses_school_term_date_idx'));
        Schema::table('students', fn (Blueprint $table) => $table->index(['school_id', 'status', 'school_class_id'], 'students_school_status_class_idx'));
        Schema::table('finance_ledger_entries', fn (Blueprint $table) => $table->index(['source_type', 'source_id', 'status'], 'ledger_source_status_idx'));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->index(['school_id', 'created_at'], 'audit_logs_school_created_idx'));
    }

    public function down(): void
    {
        Schema::table('fee_payments', fn (Blueprint $table) => $table->dropIndex('fee_payments_school_term_paid_idx'));
        Schema::table('expenses', fn (Blueprint $table) => $table->dropIndex('expenses_school_term_date_idx'));
        Schema::table('students', fn (Blueprint $table) => $table->dropIndex('students_school_status_class_idx'));
        Schema::table('finance_ledger_entries', fn (Blueprint $table) => $table->dropIndex('ledger_source_status_idx'));
        Schema::table('audit_logs', fn (Blueprint $table) => $table->dropIndex('audit_logs_school_created_idx'));
    }
};
