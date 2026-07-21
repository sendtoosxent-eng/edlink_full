<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropUnique('student_attendance_day_unique');
            $table->foreignId('subject_id')->nullable()->after('student_id')->constrained()->nullOnDelete();
            $table->foreignId('school_class_id')->nullable()->after('subject_id')->constrained('school_classes')->nullOnDelete();
            $table->foreignId('stream_id')->nullable()->after('school_class_id')->constrained()->nullOnDelete();
            $table->time('lesson_time')->nullable()->after('attendance_date');
            $table->string('session_key')->default('daily')->after('lesson_time');
            $table->unique(['student_id', 'attendance_date', 'session_key'], 'student_attendance_session_unique');
            $table->index(['school_id', 'subject_id', 'attendance_date'], 'attendance_subject_date_index');
        });
    }

    public function down(): void
    {
        Schema::table('attendance_records', function (Blueprint $table) {
            $table->dropIndex('attendance_subject_date_index');
            $table->dropUnique('student_attendance_session_unique');
            $table->dropConstrainedForeignId('subject_id');
            $table->dropConstrainedForeignId('school_class_id');
            $table->dropConstrainedForeignId('stream_id');
            $table->dropColumn(['lesson_time', 'session_key']);
            $table->unique(['student_id', 'attendance_date'], 'student_attendance_day_unique');
        });
    }
};
