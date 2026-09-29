<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('schools', fn (Blueprint $table) => $table->string('school_type', 20)->default('kindergarten')->after('name'));
        Schema::table('school_classes', function (Blueprint $table) {
            $table->string('education_stage', 30)->default('kindergarten')->after('name');
            $table->boolean('is_system')->default(false)->after('education_stage');
            $table->unsignedTinyInteger('sort_order')->default(0)->after('is_system');
        });
        Schema::table('grading_scales', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'grade']);
            $table->string('education_stage', 30)->default('primary')->after('school_id');
            $table->unsignedTinyInteger('aggregate_points')->nullable()->after('grade');
            $table->unique(['school_id', 'education_stage', 'grade'], 'grading_scales_school_stage_grade_unique');
        });
    }

    public function down(): void
    {
        Schema::table('grading_scales', function (Blueprint $table) {
            $table->dropUnique('grading_scales_school_stage_grade_unique');
            $table->dropColumn(['education_stage', 'aggregate_points']);
            $table->unique(['school_id', 'grade']);
        });
        Schema::table('school_classes', fn (Blueprint $table) => $table->dropColumn(['education_stage', 'is_system', 'sort_order']));
        Schema::table('schools', fn (Blueprint $table) => $table->dropColumn('school_type'));
    }
};
