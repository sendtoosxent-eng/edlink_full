<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_asset_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->string('code', 30);
            $table->string('name');
            $table->unsignedInteger('useful_life_months')->default(60);
            $table->string('depreciation_method', 30)->default('straight_line');
            $table->decimal('annual_rate', 7, 4)->nullable();
            $table->foreignId('asset_account_id')->constrained('ledger_accounts')->restrictOnDelete();
            $table->foreignId('accumulated_depreciation_account_id');
            $table->foreign(
                'accumulated_depreciation_account_id',
                'asset_category_accumulated_account_fk'
            )->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->foreignId('depreciation_expense_account_id');
            $table->foreign(
                'depreciation_expense_account_id',
                'asset_category_depreciation_expense_fk'
            )->references('id')->on('ledger_accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['school_id', 'code']);
        });
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('fixed_asset_category_id')->constrained()->restrictOnDelete();
            $table->foreignId('financial_account_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('custodian_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('acquisition_journal_id')->nullable()->constrained('accounting_journals')->restrictOnDelete();
            $table->string('asset_tag', 60);
            $table->string('name');
            $table->string('serial_number')->nullable();
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->date('acquisition_date');
            $table->date('in_service_date');
            $table->decimal('cost', 18, 2);
            $table->decimal('residual_value', 18, 2)->default(0);
            $table->unsignedInteger('useful_life_months');
            $table->string('depreciation_method', 30);
            $table->decimal('annual_rate', 7, 4)->nullable();
            $table->string('settlement_type', 20)->default('immediate');
            $table->string('status', 30)->default('awaiting_approval');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'asset_tag']);
            $table->index(['school_id', 'status', 'in_service_date']);
        });
        Schema::create('fixed_asset_depreciations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->restrictOnDelete();
            $table->foreignId('fixed_asset_id')->constrained()->restrictOnDelete();
            $table->foreignId('journal_id')->nullable()->constrained('accounting_journals')->restrictOnDelete();
            $table->date('period_ending');
            $table->decimal('opening_carrying_value', 18, 2);
            $table->decimal('depreciation_amount', 18, 2);
            $table->decimal('closing_carrying_value', 18, 2);
            $table->string('status', 20)->default('submitted');
            $table->foreignId('prepared_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->timestamps();
            $table->unique(['fixed_asset_id', 'period_ending']);
            $table->index(['school_id', 'period_ending', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_depreciations');
        Schema::dropIfExists('fixed_assets');
        Schema::dropIfExists('fixed_asset_categories');
    }
};
