<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class Edl03e09TeacherFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-03E09')->firstOrFail();
        $term = $school->currentTerm() ?? $school->terms()->latest('year')->firstOrFail();
        $classes = $school->classes()->orderBy('sort_order')->orderBy('name')->take(4)->get();
        $subjects = DB::table('subjects')->where('school_id', $school->id)->orderBy('name')->take(5)->get();

        if ($classes->isEmpty() || $subjects->isEmpty()) {
            $this->command?->error('The target school needs at least one class and one subject.');
            return;
        }

        $designation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Seeded Subject Teacher'],
            ['permissions' => DesignationPermissions::defaults()['Subject Teacher']]
        );

        $teachers = collect([
            ['name'=>'Grace Namusoke (Test)','email'=>'grace.teacher.test@edlink.local','staff_number'=>'EDL03E09-T01','job_title'=>'Mathematics Teacher'],
            ['name'=>'Daniel Kato (Test)','email'=>'daniel.teacher.test@edlink.local','staff_number'=>'EDL03E09-T02','job_title'=>'Science Teacher'],
            ['name'=>'Sarah Atim (Test)','email'=>'sarah.teacher.test@edlink.local','staff_number'=>'EDL03E09-T03','job_title'=>'English Teacher'],
        ])->map(function (array $data) use ($school, $designation) {
            $teacher = User::updateOrCreate(
                ['school_id' => $school->id, 'email' => $data['email']],
                [
                    'designation_id' => $designation->id,
                    'name' => $data['name'],
                    'password' => 'TeacherTest@2026',
                    'role' => 'teacher',
                    'staff_number' => $data['staff_number'],
                    'job_title' => $data['job_title'],
                    'employment_status' => 'active',
                    'joined_at' => now()->subMonths(4)->toDateString(),
                ]
            );
            $teacher->forceFill(['email_verified_at' => now()])->save();
            return $teacher;
        });
        $classes->first()->update(['class_teacher_user_id' => $teachers->first()->id]);

        foreach ($teachers as $index => $teacher) {
            $class = $classes[$index % $classes->count()];
            $subject = $subjects[$index % $subjects->count()];
            DB::table('staff_subjects')->updateOrInsert(
                ['user_id'=>$teacher->id,'subject_id'=>$subject->id,'school_class_id'=>$class->id],
                ['school_id'=>$school->id,'term_id'=>$term->id,'created_at'=>now(),'updated_at'=>now()]
            );
            DB::table('class_subjects')->updateOrInsert(
                ['term_id'=>$term->id,'school_class_id'=>$class->id,'subject_id'=>$subject->id],
                ['school_id'=>$school->id,'created_at'=>now(),'updated_at'=>now()]
            );
        }

        // A live lesson makes the new countdown card visible immediately after login.
        $liveStart = now()->subMinutes(5);
        $liveEnd = now()->addMinutes(35);
        $this->upsertSlot($school->id, $term->id, $classes[0]->id, $subjects[0]->id, $teachers[0]->id, now()->format('l'), $liveStart->format('H:i'), $liveEnd->format('H:i'), 'Seeded live test lesson');

        $weeklySlots = [
            [0,0,0,'Monday','08:00','08:40'], [0,0,0,'Wednesday','10:00','10:40'], [0,0,0,'Friday','11:20','12:00'],
            [1,1,1,'Tuesday','09:00','09:40'], [1,1,1,'Thursday','11:20','12:00'], [1,1,1,'Friday','14:00','14:40'],
            [2,2,2,'Monday','14:00','14:40'], [2,2,2,'Wednesday','08:50','09:30'], [2,2,2,'Thursday','13:10','13:50'],
        ];
        foreach ($weeklySlots as [$teacherIndex,$classIndex,$subjectIndex,$day,$start,$end]) {
            $this->upsertSlot($school->id, $term->id, $classes[$classIndex % $classes->count()]->id, $subjects[$subjectIndex % $subjects->count()]->id, $teachers[$teacherIndex]->id, $day, $start, $end, 'Seeded teacher test lesson');
        }

        $house = DB::table('student_houses')->where('school_id', $school->id)->orderBy('id')->first();
        if ($house) DB::table('student_houses')->where('id', $house->id)->update(['patron_user_id'=>$teachers[1]->id,'updated_at'=>now()]);

        $clubId = DB::table('student_clubs')->where('school_id',$school->id)->where('name','Teacher Test Club')->value('id');
        if (! $clubId) {
            $clubId = DB::table('student_clubs')->insertGetId([
                'school_id'=>$school->id,'name'=>'Teacher Test Club','color'=>'#10b981',
                'patron_user_id'=>$teachers[2]->id,'description'=>'Seeded club for testing patron member lists and Excel exports.',
                'maximum_members'=>30,'created_at'=>now(),'updated_at'=>now(),
            ]);
        } else {
            DB::table('student_clubs')->where('id',$clubId)->update(['patron_user_id'=>$teachers[2]->id,'updated_at'=>now()]);
        }
        $studentIds = $school->students()->where('status','active')->orderBy('name')->take(12)->pluck('id');
        foreach ($studentIds as $studentId) {
            DB::table('student_club_memberships')->updateOrInsert(
                ['student_club_id'=>$clubId,'student_id'=>$studentId],
                ['school_id'=>$school->id,'assigned_by'=>$teachers[2]->id,'created_at'=>now(),'updated_at'=>now()]
            );
        }

        $this->command?->info('Seeded 3 test teachers, assignments, timetable lessons, and patron data for EDL-03E09.');
        $this->command?->line('Teacher login password: TeacherTest@2026');
    }

    private function upsertSlot(int $schoolId, int $termId, int $classId, int $subjectId, int $teacherId, string $day, string $start, string $end, string $label): void
    {
        $conflict = DB::table('timetable_slots')
            ->where('school_id',$schoolId)->where('term_id',$termId)->where('school_class_id',$classId)->where('day_of_week',$day)
            ->where('starts_at','<',$end)->where('ends_at','>',$start)
            ->where(fn($query)=>$query->where('user_id','!=',$teacherId)->orWhere('starts_at','!=',$start))
            ->exists();
        if ($conflict) {
            $this->command?->warn("Skipped {$day} {$start} test slot because the class is already occupied.");
            return;
        }
        DB::table('timetable_slots')->updateOrInsert(
            ['school_id'=>$schoolId,'term_id'=>$termId,'user_id'=>$teacherId,'day_of_week'=>$day,'starts_at'=>$start],
            ['school_class_id'=>$classId,'stream_id'=>null,'subject_id'=>$subjectId,'ends_at'=>$end,'label'=>$label,'created_at'=>now(),'updated_at'=>now()]
        );
    }
}
