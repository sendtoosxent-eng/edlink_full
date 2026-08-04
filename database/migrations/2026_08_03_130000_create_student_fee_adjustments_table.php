<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('student_fee_adjustments', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 40);
            $table->string('calculation_type', 20);
            $table->decimal('value', 14, 2);
            $table->decimal('amount', 14, 2);
            $table->text('reason');
            $table->string('status', 20)->default('pending');
            $table->text('review_notes')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();
            $table->index(['school_id', 'term_id', 'status']);
            $table->index(['student_id', 'term_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_fee_adjustments');
    }
};
