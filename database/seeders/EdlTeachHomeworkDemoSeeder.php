<?php

namespace Database\Seeders;

use App\Models\HomeworkAssignment;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EdlTeachHomeworkDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-TEACH')->where('is_demo', true)->firstOrFail();
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen() || $term->isLocked()) {
            throw new \RuntimeException('EDL-TEACH must have an open, unlocked current term.');
        }
        $teacher = $school->users()->where('role', 'teacher')
            ->orderByRaw("CASE WHEN email = 'class.teacher@edlink.local' THEN 0 ELSE 1 END")
            ->orderBy('id')->firstOrFail();
        $classes = $school->classes()->get();
        $tasks = [
            ['Mathematics', 'Number practice', "Demo practice: show your working.\n1. Calculate 24 + 18.\n2. Calculate 60 - 27.\n3. Calculate 8 x 7.\n4. Share 36 equally among 6 learners.\n5. Write your own number problem and solve it."],
            ['English', 'My school day', "Demo writing practice: write five sentences about your school day. Use capital letters and full stops. Underline three nouns and two verbs. Type your answer or attach a photo of your work."],
            ['Science', 'Healthy habits', "Demo science practice: list five ways to stay healthy. Explain why washing hands is important. Draw and label three items used to keep your surroundings clean. Type your answer or attach a photo of your work."],
        ];
        DB::transaction(function () use ($school, $term, $teacher, $classes, $tasks) {
            foreach ($tasks as $index => [$name, $title, $instructions]) {
                $subject = Subject::firstOrCreate(['school_id' => $school->id, 'name' => $name], ['code' => strtoupper(substr($name, 0, 3))]);
                foreach ($classes as $class) {
                    HomeworkAssignment::firstOrCreate([
                        'school_id' => $school->id, 'term_id' => $term->id,
                        'school_class_id' => $class->id, 'title' => 'App Demo: '.$title,
                    ], [
                        'teacher_id' => $teacher->id, 'subject_id' => $subject->id,
                        'stream_id' => null, 'instructions' => $instructions, 'maximum_score' => 20,
                        'published_at' => now(), 'due_at' => today()->addDays($index + 3)->setTime(17, 0),
                    ]);
                }
            }
        });
        $this->command?->info("EDL-TEACH: {$term->name}, {$term->year}; 3 published homework assignments for each of {$classes->count()} classes. Teacher: {$teacher->email}. Existing homework and submissions preserved.");
    }
}
