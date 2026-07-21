<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fee_structures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('student_category_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            $table->timestamps();

            // One fee amount per class + category + term — this is what a
            // student's class+category assignment will look up automatically.
            $table->unique(['school_class_id', 'student_category_id', 'term_id'], 'fee_structure_unique_mapping');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fee_structures');
    }
};
