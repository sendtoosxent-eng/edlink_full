<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('category'); // Includes payroll.
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->date('expense_date');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('cash_pool_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('fee_payment_id')->nullable()->constrained()->cascadeOnDelete();
            $table->foreignId('expense_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('direction'); // credit | debit
            $table->decimal('amount', 12, 2);
            $table->string('description')->nullable();
            $table->timestamp('transacted_at');
            $table->foreignId('recorded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique('fee_payment_id');
            $table->unique('expense_id');
            $table->index(['school_id', 'term_id', 'direction']);
        });

        DB::table('fee_payments')->orderBy('id')->each(function ($payment) {
            DB::table('cash_pool_entries')->insert([
                'school_id' => $payment->school_id,
                'term_id' => $payment->term_id,
                'fee_payment_id' => $payment->id,
                'direction' => 'credit',
                'amount' => $payment->amount,
                'description' => 'Student fee payment',
                'transacted_at' => $payment->paid_at,
                'recorded_by' => $payment->recorded_by,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_pool_entries');
        Schema::dropIfExists('expenses');
    }
};
