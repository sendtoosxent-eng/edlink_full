<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_category_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->timestamps();
            $table->unique(['school_id', 'term_id', 'name']);
        });

        Schema::table('exam_categories', function (Blueprint $table) {
            $table->foreignId('exam_category_group_id')->nullable()->after('term_id')->constrained('exam_category_groups')->cascadeOnDelete();
            // MySQL may use the composite unique index below for the school_id
            // foreign key. Give that constraint its own index before replacing it.
            $table->index('school_id');
        });

        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropUnique(['school_id', 'term_id', 'name']);
            $table->unique(['exam_category_group_id', 'name']);
        });

        Schema::create('exam_category_group_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('school_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->foreignId('school_class_id')->constrained('school_classes')->cascadeOnDelete();
            $table->foreignId('exam_category_group_id')->constrained('exam_category_groups')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['school_id', 'term_id', 'school_class_id'], 'exam_category_group_class_unique');
        });

        DB::table('exam_categories')->whereNotNull('term_id')->select('school_id', 'term_id')->distinct()->orderBy('school_id')->orderBy('term_id')->get()
            ->each(function (object $scope): void {
                $now = now();
                $groupId = DB::table('exam_category_groups')->insertGetId([
                    'school_id' => $scope->school_id,
                    'term_id' => $scope->term_id,
                    'name' => 'General',
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
                DB::table('exam_categories')->where('school_id', $scope->school_id)->where('term_id', $scope->term_id)
                    ->update(['exam_category_group_id' => $groupId]);

                DB::table('exams')->where('school_id', $scope->school_id)->where('term_id', $scope->term_id)
                    ->whereIn('exam_category_id', DB::table('exam_categories')->where('exam_category_group_id', $groupId)->select('id'))
                    ->distinct()->pluck('school_class_id')->each(function (int $classId) use ($scope, $groupId, $now): void {
                        DB::table('exam_category_group_assignments')->insertOrIgnore([
                            'school_id' => $scope->school_id,
                            'term_id' => $scope->term_id,
                            'school_class_id' => $classId,
                            'exam_category_group_id' => $groupId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    });
            });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_category_group_assignments');
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropUnique(['exam_category_group_id', 'name']);
            $table->dropConstrainedForeignId('exam_category_group_id');
            $table->unique(['school_id', 'term_id', 'name']);
        });
        Schema::table('exam_categories', function (Blueprint $table) {
            $table->dropIndex(['school_id']);
        });
        Schema::dropIfExists('exam_category_groups');
    }
};
