<?php

namespace Database\Seeders;

use App\Models\School;
use App\Services\SchoolAcademicSetup;
use Illuminate\Database\Seeder;

class CurrentDemoSecondarySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('is_demo', true)->latest('id')->firstOrFail();

        $school->update(['school_type' => 'secondary']);
        SchoolAcademicSetup::provision($school);

        $school->classes()->where('is_system', true)->whereNotIn('education_stage', ['lower_secondary', 'advanced_level'])
            ->get()->each(function ($class) {
                if (! $class->students()->exists() && ! $class->enrolments()->exists()) {
                    $class->delete();
                }
            });

        $this->command?->info("{$school->name} ({$school->school_number}) is now a secondary demo school.");
    }
}
