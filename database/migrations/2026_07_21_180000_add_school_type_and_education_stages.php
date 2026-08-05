<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Schools
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasColumn('schools', 'school_type')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->string('school_type', 20)
                    ->default('kindergarten')
                    ->after('name');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | School classes
        |--------------------------------------------------------------------------
        */

        if (! Schema::hasColumn('school_classes', 'education_stage')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->string('education_stage', 30)
                    ->default('kindergarten')
                    ->after('name');
            });
        }

        if (! Schema::hasColumn('school_classes', 'is_system')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->boolean('is_system')
                    ->default(false)
                    ->after('education_stage');
            });
        }

        if (! Schema::hasColumn('school_classes', 'sort_order')) {
            Schema::table('school_classes', function (Blueprint $table) {
                $table->unsignedTinyInteger('sort_order')
                    ->default(0)
                    ->after('is_system');
            });
        }

        /*
        |--------------------------------------------------------------------------
        | Grading scales
        |--------------------------------------------------------------------------
        |
        | MySQL may be using the old unique index to support the school_id
        | foreign key. Create a dedicated school_id index before dropping it.
        |
        */

        if (! Schema::hasIndex(
            'grading_scales',
            'grading_scales_school_id_index'
        )) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->index(
                    'school_id',
                    'grading_scales_school_id_index'
                );
            });
        }

        if (Schema::hasIndex(
            'grading_scales',
            'grading_scales_school_id_grade_unique'
        )) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->dropUnique(
                    'grading_scales_school_id_grade_unique'
                );
            });
        }

        if (! Schema::hasColumn('grading_scales', 'education_stage')) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->string('education_stage', 30)
                    ->default('primary')
                    ->after('school_id');
            });
        }

        if (! Schema::hasColumn('grading_scales', 'aggregate_points')) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->unsignedTinyInteger('aggregate_points')
                    ->nullable()
                    ->after('grade');
            });
        }

        if (! Schema::hasIndex(
            'grading_scales',
            'grading_scales_school_stage_grade_unique'
        )) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->unique(
                    ['school_id', 'education_stage', 'grade'],
                    'grading_scales_school_stage_grade_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex(
            'grading_scales',
            'grading_scales_school_stage_grade_unique'
        )) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->dropUnique(
                    'grading_scales_school_stage_grade_unique'
                );
            });
        }

        if (Schema::hasColumn('grading_scales', 'aggregate_points')) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->dropColumn('aggregate_points');
            });
        }

        if (Schema::hasColumn('grading_scales', 'education_stage')) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->dropColumn('education_stage');
            });
        }

        if (! Schema::hasIndex(
            'grading_scales',
            'grading_scales_school_id_grade_unique'
        )) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->unique(
                    ['school_id', 'grade'],
                    'grading_scales_school_id_grade_unique'
                );
            });
        }

        /*
         * The restored unique index can support the school_id foreign key,
         * so the temporary standalone index can now be removed.
         */
        if (Schema::hasIndex(
            'grading_scales',
            'grading_scales_school_id_index'
        )) {
            Schema::table('grading_scales', function (Blueprint $table) {
                $table->dropIndex(
                    'grading_scales_school_id_index'
                );
            });
        }

        $schoolClassColumns = [];

        if (Schema::hasColumn('school_classes', 'education_stage')) {
            $schoolClassColumns[] = 'education_stage';
        }

        if (Schema::hasColumn('school_classes', 'is_system')) {
            $schoolClassColumns[] = 'is_system';
        }

        if (Schema::hasColumn('school_classes', 'sort_order')) {
            $schoolClassColumns[] = 'sort_order';
        }

        if ($schoolClassColumns !== []) {
            Schema::table('school_classes', function (Blueprint $table) use ($schoolClassColumns) {
                $table->dropColumn($schoolClassColumns);
            });
        }

        if (Schema::hasColumn('schools', 'school_type')) {
            Schema::table('schools', function (Blueprint $table) {
                $table->dropColumn('school_type');
            });
        }
    }
};