<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('arrears', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->foreignId('from_term_id')->constrained('terms')->cascadeOnDelete();
            $table->decimal('amount', 12, 2);
            // Which term this arrear becomes due in — filled in once the next term opens.
            $table->foreignId('applied_term_id')->nullable()->constrained('terms')->nullOnDelete();
            $table->boolean('applied')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arrears');
    }
};
