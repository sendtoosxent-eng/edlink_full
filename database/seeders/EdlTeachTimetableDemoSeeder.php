<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EdlTeachTimetableDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-TEACH')->where('is_demo', true)->firstOrFail();
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen() || $term->isLocked()) {
            throw new \RuntimeException('EDL-TEACH must have an open, unlocked current term.');
        }
        $classes = $school->classes()->get();
        $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
        DB::transaction(function () use ($school, $term, $classes, $days) {
            foreach (['Mathematics', 'English', 'Science'] as $index => $name) {
                $subject = Subject::firstOrCreate(['school_id' => $school->id, 'name' => $name], ['code' => strtoupper(substr($name, 0, 3))]);
                foreach ($classes as $class) {
                    foreach ($days as $day) {
                        $key = [
                            'school_id' => $school->id, 'term_id' => $term->id,
                            'school_class_id' => $class->id, 'day_of_week' => $day,
                            'label' => 'App demo lesson: '.$name,
                        ];
                        if (! DB::table('timetable_slots')->where($key)->exists()) {
                            DB::table('timetable_slots')->insert($key + [
                                'subject_id' => $subject->id, 'stream_id' => null,
                                'starts_at' => sprintf('%02d:00:00', 8 + $index),
                                'ends_at' => sprintf('%02d:40:00', 8 + $index),
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                        }
                    }
                }
            }
        });
        $this->command?->info("EDL-TEACH: {$term->name}, {$term->year}; {$classes->count()} classes, 3 demo lessons per day, Monday–Sunday. Existing lessons preserved.");
    }
}
