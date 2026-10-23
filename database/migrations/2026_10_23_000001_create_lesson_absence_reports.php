<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lesson_absence_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('timetable_slot_id')->nullable()->constrained('timetable_slots')->nullOnDelete();
            $table->date('lesson_date');
            $table->json('lesson_details');
            $table->timestamps();
            $table->unique(['user_id', 'timetable_slot_id', 'lesson_date'], 'lesson_absence_teacher_slot_date_unique');
            $table->index(['school_id', 'lesson_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lesson_absence_reports');
    }
};
