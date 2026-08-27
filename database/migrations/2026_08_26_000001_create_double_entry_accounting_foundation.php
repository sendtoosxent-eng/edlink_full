<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_years', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open');
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'starts_on']);
            $table->index(['school_id', 'status', 'ends_on']);
        });

        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status')->default('open'); // open | soft_closed | locked
            $table->text('status_reason')->nullable();
            $table->foreignId('status_changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('status_changed_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'starts_on']);
            $table->index(['school_id', 'status', 'ends_on']);
        });

        Schema::create('ledger_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('ledger_accounts')->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->string('account_class', 20); // asset, liability, equity, income, expense
            $table->string('subtype', 60)->nullable();
            $table->string('normal_balance', 6); // debit | credit
            $table->string('currency', 3);
            $table->boolean('accepts_postings')->default(true);
            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamp('archived_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'code']);
            $table->index(['school_id', 'account_class', 'is_active']);
        });

        Schema::create('cost_centres', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('accounting_funds', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->boolean('is_restricted')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });

        Schema::create('account_mappings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('mapping_type', 80);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->json('metadata')->nullable();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'mapping_type', 'source_type', 'source_id'], 'account_mapping_source_unique');
            $table->index(['school_id', 'mapping_type']);
        });

        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('accounting_period_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('number', 50);
            $table->date('journal_date');
            $table->string('reference')->nullable();
            $table->text('description');
            $table->string('journal_type', 40)->default('manual');
            $table->string('status', 20)->default('draft');
            $table->string('currency', 3);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('idempotency_key', 160)->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('accounting_journals')->restrictOnDelete();
            $table->text('rejection_reason')->nullable();
            $table->text('reversal_reason')->nullable();
            $table->string('evidence_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('posted_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'number']);
            $table->unique(['school_id', 'idempotency_key']);
            $table->index(['school_id', 'status', 'journal_date']);
            $table->index(['school_id', 'source_type', 'source_id']);
        });

        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('accounting_journal_id')->constrained()->restrictOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres')->restrictOnDelete();
            $table->foreignId('fund_id')->nullable()->constrained('accounting_funds')->restrictOnDelete();
            $table->foreignId('student_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('employee_id')->nullable()->constrained('users')->restrictOnDelete();
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->string('description')->nullable();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->timestamps();
            $table->index(['school_id', 'ledger_account_id', 'accounting_journal_id'], 'journal_line_account_lookup');
            $table->index(['school_id', 'student_id']);
        });

        Schema::create('student_fee_assessments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('student_id')->constrained()->restrictOnDelete();
            $table->foreignId('term_id')->constrained()->restrictOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('fee_item_code', 60)->default('tuition');
            $table->string('description');
            $table->decimal('amount', 18, 2);
            $table->string('status')->default('draft');
            $table->foreignId('journal_id')->nullable()->constrained('accounting_journals')->restrictOnDelete();
            $table->string('idempotency_key', 160);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['school_id', 'idempotency_key']);
            $table->index(
                ['school_id', 'student_id', 'term_id', 'status'],
                'fee_assessment_student_term_status_idx'
            );
        });

        Schema::create('accounting_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('tax_number')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'name']);
        });

        Schema::table('accounting_journal_lines', function (Blueprint $table) {
            $table->foreign('supplier_id')->references('id')->on('accounting_suppliers')->restrictOnDelete();
        });

        Schema::create('accounting_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('fiscal_year_id')->constrained()->restrictOnDelete();
            $table->string('name');
            $table->string('status')->default('draft');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'fiscal_year_id', 'name']);
        });

        Schema::create('accounting_budget_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('accounting_budget_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ledger_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('accounting_period_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('cost_centre_id')->nullable()->constrained('cost_centres')->restrictOnDelete();
            $table->foreignId('fund_id')->nullable()->constrained('accounting_funds')->restrictOnDelete();
            $table->decimal('amount', 18, 2);
            $table->timestamps();
        });

        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->foreignId('ledger_account_id')->nullable()->constrained('ledger_accounts')->restrictOnDelete();
            $table->index(['school_id', 'ledger_account_id']);
        });
    }

    public function down(): void
    {
        Schema::table('financial_accounts', function (Blueprint $table) {
            $table->dropConstrainedForeignId('ledger_account_id');
        });
        Schema::dropIfExists('accounting_budget_lines');
        Schema::dropIfExists('accounting_budgets');
        Schema::table('accounting_journal_lines', function (Blueprint $table) {
            $table->dropForeign(['supplier_id']);
        });
        Schema::dropIfExists('accounting_suppliers');
        Schema::dropIfExists('student_fee_assessments');
        Schema::dropIfExists('accounting_journal_lines');
        Schema::dropIfExists('accounting_journals');
        Schema::dropIfExists('account_mappings');
        Schema::dropIfExists('accounting_funds');
        Schema::dropIfExists('cost_centres');
        Schema::dropIfExists('ledger_accounts');
        Schema::dropIfExists('accounting_periods');
        Schema::dropIfExists('fiscal_years');
    }
};
