<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('staff_salary_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('period', 7);
            $table->decimal('salary', 12, 2);
            $table->timestamps();
            $table->unique(['school_id', 'user_id', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_salary_balances');
    }
};
