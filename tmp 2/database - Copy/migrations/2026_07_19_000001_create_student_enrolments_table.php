<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_enrolments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('stream_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('fee_structure_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('base_fee_amount', 12, 2)->default(0);
            $table->string('status')->default('active'); // active | inactive | transferred | withdrawn | graduated
            $table->string('promotion_outcome')->nullable(); // promoted | repeated | graduated | transferred | withdrawn
            $table->date('enrolled_at');
            $table->date('exited_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'term_id'], 'student_term_enrolment_unique');
            $table->index(['school_id', 'term_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_enrolments');
    }
};
