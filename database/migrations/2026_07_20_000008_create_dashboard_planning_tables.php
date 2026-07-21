<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('school_events', function (Blueprint $table) {
            $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); $table->date('event_date'); $table->string('type')->default('general'); $table->text('description')->nullable(); $table->timestamps();
            $table->index(['school_id','event_date']);
        });
        Schema::create('timetable_slots', function (Blueprint $table) {
            $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('term_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->constrained('school_classes')->nullOnDelete(); $table->foreignId('stream_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('day_of_week'); $table->time('starts_at'); $table->time('ends_at'); $table->string('label')->nullable(); $table->timestamps();
            $table->index(['school_id','term_id','school_class_id']);
        });
        Schema::create('school_notifications', function (Blueprint $table) {
            $table->id(); $table->foreignId('school_id')->constrained()->cascadeOnDelete(); $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('title'); $table->text('message'); $table->string('type')->default('info'); $table->timestamp('read_at')->nullable(); $table->timestamps();
            $table->index(['school_id','created_at']);
        });
    }
    public function down(): void { Schema::dropIfExists('school_notifications'); Schema::dropIfExists('timetable_slots'); Schema::dropIfExists('school_events'); }
};
