<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_backups', function (Blueprint $table) {
            $table->id();
            $table->string('disk')->default('local');
            $table->string('path');
            $table->unsignedBigInteger('size')->default(0);
            $table->string('checksum', 64);
            $table->string('status')->default('verified');
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('restored_tested_at')->nullable();
            $table->text('failure')->nullable();
            $table->timestamps();
        });

        Schema::create('finance_ledger_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('reference')->unique();
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('entry_type');
            $table->string('direction');
            $table->decimal('amount', 14, 2);
            $table->string('description');
            $table->string('status')->default('posted');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('reversal_of_id')->nullable()->constrained('finance_ledger_entries')->nullOnDelete();
            $table->text('reversal_reason')->nullable();
            $table->timestamp('posted_at');
            $table->timestamps();
            $table->unique(['source_type', 'source_id']);
            $table->index(['school_id', 'term_id', 'status', 'posted_at']);
        });

        Schema::create('finance_reconciliations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->date('period_ending');
            $table->decimal('statement_balance', 14, 2);
            $table->decimal('ledger_balance', 14, 2);
            $table->decimal('difference', 14, 2);
            $table->text('notes')->nullable();
            $table->foreignId('reconciled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reconciled_at');
            $table->timestamps();
            $table->unique(['school_id', 'period_ending']);
        });

        Schema::create('privacy_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('subject_type');
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('request_type');
            $table->string('status')->default('pending');
            $table->text('reason')->nullable();
            $table->string('verification_token_hash', 64);
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('result')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'status', 'request_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('privacy_requests');
        Schema::dropIfExists('finance_reconciliations');
        Schema::dropIfExists('finance_ledger_entries');
        Schema::dropIfExists('system_backups');
    }
};
