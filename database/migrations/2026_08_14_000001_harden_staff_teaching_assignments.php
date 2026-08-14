<?php

use App\Models\Designation;
use App\Models\School;
use App\Support\DesignationPermissions;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('staff_subjects', function (Blueprint $table): void {
            $table->dropUnique(['user_id', 'subject_id', 'school_class_id']);
            $table->unique(
                ['user_id', 'term_id', 'subject_id', 'school_class_id'],
                'staff_subjects_user_term_subject_class_unique',
            );
        });

        School::query()->each(function (School $school): void {
            foreach (DesignationPermissions::defaults() as $name => $permissions) {
                Designation::firstOrCreate(
                    ['school_id' => $school->id, 'name' => $name],
                    [
                        'description' => 'Default Edlink access designation.',
                        'permissions' => $permissions,
                    ],
                );
            }
        });
    }

    public function down(): void
    {
        Schema::table('staff_subjects', function (Blueprint $table): void {
            $table->dropUnique('staff_subjects_user_term_subject_class_unique');
            $table->unique(['user_id', 'subject_id', 'school_class_id']);
        });
    }
};
