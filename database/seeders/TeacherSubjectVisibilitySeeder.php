<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Models\SchoolSetting;
use App\Support\DemoAccounts;
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSubjectVisibilitySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['school_number' => DemoAccounts::schoolNumber()],
            [
                'name' => 'Teacher Visibility Demo School',
                'slug' => 'teacher-visibility-demo',
                'school_type' => 'primary',
                'status' => 'active',
                'license_plan' => 'premium',
                'license_status' => 'active',
                'license_started_at' => now()->subDay(),
                'license_expires_at' => now()->addYear(),
                'is_demo' => true,
                'demo_expires_at' => now()->addYear(),
            ],
        );

        $term = Term::updateOrCreate(
            ['school_id' => $school->id, 'year' => now()->year, 'term_number' => 1],
            ['name' => 'Term 1', 'is_current' => true, 'status' => 'open', 'locked' => false],
        );

        $primaryFive = SchoolClass::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Primary Five'],
            ['education_stage' => 'primary', 'sort_order' => 5],
        );
        $primarySix = SchoolClass::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Primary Six'],
            ['education_stage' => 'primary', 'sort_order' => 6],
        );

        $subjects = collect([
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'Science', 'code' => 'SCI'],
        ])->mapWithKeys(function (array $data) use ($school) {
            $subject = Subject::updateOrCreate(
                ['school_id' => $school->id, 'name' => $data['name']],
                ['code' => $data['code']],
            );

            return [$data['name'] => $subject];
        });

        $classTeacherDesignation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Class Teacher'],
            ['permissions' => DesignationPermissions::defaults()['Class Teacher']],
        );
        $subjectTeacherDesignation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Subject Teacher'],
            ['permissions' => DesignationPermissions::defaults()['Subject Teacher']],
        );
        $bursarDesignation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Bursar'],
            ['permissions' => DesignationPermissions::defaults()['Bursar']],
        );

        $classTeacher = $this->teacher(
            $school,
            $classTeacherDesignation,
            'Demo Class Teacher',
            'class.teacher@edlink.local',
            'TEACH-CLASS-01',
        );
        $subjectTeacher = $this->teacher(
            $school,
            $subjectTeacherDesignation,
            'Demo Subject Teacher',
            'subject.teacher@edlink.local',
            'TEACH-SUBJECT-01',
        );

        $primaryFive->update(['class_teacher_user_id' => $classTeacher->id]);

        foreach ([$primaryFive, $primarySix] as $class) {
            foreach ($subjects as $subject) {
                DB::table('class_subjects')->updateOrInsert(
                    ['term_id' => $term->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id],
                    ['school_id' => $school->id, 'created_at' => now(), 'updated_at' => now()],
                );
            }

            $exam = Exam::updateOrCreate(
                ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => 'Visibility Test Exam'],
                ['status' => 'draft'],
            );
            foreach ($subjects as $subject) {
                ExamPaper::updateOrCreate(
                    ['exam_id' => $exam->id, 'subject_id' => $subject->id],
                    ['maximum_score' => 100, 'weighting' => 1],
                );
            }
        }

        DB::table('staff_subjects')->updateOrInsert(
            [
                'school_id' => $school->id,
                'term_id' => $term->id,
                'user_id' => $subjectTeacher->id,
                'school_class_id' => $primaryFive->id,
                'subject_id' => $subjects['Mathematics']->id,
            ],
            ['created_at' => now(), 'updated_at' => now()],
        );
        DB::table('staff_subjects')->updateOrInsert(
            [
                'school_id' => $school->id,
                'term_id' => $term->id,
                'user_id' => $classTeacher->id,
                'school_class_id' => $primarySix->id,
                'subject_id' => $subjects['English']->id,
            ],
            ['created_at' => now(), 'updated_at' => now()],
        );

        foreach ([
            [$primaryFive, 'P5-001', 'Amina Class Learner'],
            [$primarySix, 'P6-001', 'Brian Subject Learner'],
        ] as [$class, $admissionNumber, $name]) {
            Student::updateOrCreate(
                ['school_id' => $school->id, 'admission_no' => $admissionNumber],
                ['school_class_id' => $class->id, 'term_id' => $term->id, 'name' => $name, 'status' => 'active'],
            );
        }

        $this->account($school, 'Demo School Administrator', 'admin@edlink.local', 'admin', null, 'DEMO-ADMIN-01');
        $this->account($school, 'Demo School Bursar', 'bursar@edlink.local', 'bursar', $bursarDesignation, 'DEMO-BURSAR-01');
        $parent = $this->account($school, 'Demo Parent', 'parent@edlink.local', 'parent');
        $studentUser = $this->account($school, 'Amina Class Learner', 'student@edlink.local', 'student');
        $learner = Student::where('school_id', $school->id)->where('admission_no', 'P5-001')->firstOrFail();
        $parent->portalStudents()->syncWithoutDetaching([
            $learner->id => ['school_id' => $school->id, 'relationship' => 'Guardian'],
        ]);
        $studentUser->portalStudents()->syncWithoutDetaching([
            $learner->id => ['school_id' => $school->id, 'relationship' => 'Student'],
        ]);
        $learner->guardians()->updateOrCreate(
            ['email' => $parent->email],
            ['name' => $parent->name, 'relationship' => 'Guardian', 'is_primary' => true],
        );

        $this->seedDemoExperience($school, $term, $primaryFive, $primarySix, $subjects, $classTeacher, $studentUser);
        SchoolSetting::setValue($school->id, 'public_demo_seed_version', DemoAccounts::SEED_VERSION);

        $this->command?->info('Teacher subject-visibility data seeded for EDL-TEACH.');
        $this->command?->line('All demo accounts use password: TeacherTest@2026');
    }

    private function seedDemoExperience(
        School $school,
        Term $term,
        SchoolClass $primaryFive,
        SchoolClass $primarySix,
        $subjects,
        User $classTeacher,
        User $studentUser,
    ): void {
        $admin = User::where('school_id', $school->id)->where('email', 'admin@edlink.local')->firstOrFail();
        $students = Student::where('school_id', $school->id)->orderBy('id')->get();

        foreach ([
            ['Welcome to the Edlink demo', 'Explore academics, finance, attendance, activities and communication using the role-based demo accounts.', 'info', null],
            ['Term results published', 'The latest assessment results are ready to view in the Results area.', 'success', null],
            ['Sports day reminder', 'Inter-house sports day starts Friday at 9:00 AM. Learners should wear their house colours.', 'warning', null],
            ['Homework feedback ready', 'Your Mathematics homework has been reviewed by the class teacher.', 'success', $studentUser->id],
        ] as [$title, $message, $type, $userId]) {
            DB::table('school_notifications')->updateOrInsert(
                ['school_id' => $school->id, 'user_id' => $userId, 'title' => $title],
                ['message' => $message, 'type' => $type, 'read_at' => null, 'created_at' => now()->subMinutes(random_int(5, 240)), 'updated_at' => now()],
            );
        }

        foreach ($students as $index => $student) {
            foreach (range(1, 7) as $daysAgo) {
                $date = now()->subDays($daysAgo);
                if ($date->isWeekend()) continue;
                DB::table('attendance_records')->updateOrInsert(
                    ['student_id' => $student->id, 'attendance_date' => $date->toDateString()],
                    ['school_id' => $school->id, 'term_id' => $term->id, 'status' => ($daysAgo === 3 && $index === 1) ? 'late' : 'present', 'recorded_by' => $classTeacher->id, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        $exam = Exam::where('school_id', $school->id)->where('school_class_id', $primaryFive->id)->where('name', 'Visibility Test Exam')->firstOrFail();
        $exam->update(['status' => 'published']);
        foreach ($exam->papers as $paperIndex => $paper) {
            DB::table('exam_paper_submissions')->updateOrInsert(
                ['exam_paper_id' => $paper->id],
                ['status' => 'approved', 'submitted_by' => $classTeacher->id, 'submitted_at' => now()->subDay(), 'approved_by' => $admin->id, 'approved_at' => now()->subHours(12), 'created_at' => now(), 'updated_at' => now()],
            );
            foreach ($students->where('school_class_id', $primaryFive->id) as $student) {
                DB::table('exam_marks')->updateOrInsert(
                    ['exam_paper_id' => $paper->id, 'student_id' => $student->id],
                    ['score' => 76 + ($paperIndex * 5), 'entered_by' => $classTeacher->id, 'created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        foreach ([['Yellow House', '#facc15'], ['Blue House', '#3b82f6']] as [$name, $color]) {
            DB::table('student_houses')->updateOrInsert(['school_id' => $school->id, 'name' => $name], ['color' => $color, 'patron_user_id' => $classTeacher->id, 'description' => 'A demo learner house.', 'created_at' => now(), 'updated_at' => now()]);
        }
        $houses = DB::table('student_houses')->where('school_id', $school->id)->orderBy('id')->get();
        foreach ($students as $index => $student) {
            DB::table('student_house_memberships')->updateOrInsert(['school_id' => $school->id, 'student_id' => $student->id], ['student_house_id' => $houses[$index % $houses->count()]->id, 'allocation_method' => 'automatic', 'assigned_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        foreach ([['Science Club', '#10b981'], ['Debate Club', '#8b5cf6']] as [$name, $color]) {
            DB::table('student_clubs')->updateOrInsert(['school_id' => $school->id, 'name' => $name], ['color' => $color, 'patron_user_id' => $classTeacher->id, 'description' => 'A demo co-curricular club.', 'maximum_members' => 40, 'created_at' => now(), 'updated_at' => now()]);
        }
        $clubs = DB::table('student_clubs')->where('school_id', $school->id)->orderBy('id')->get();
        foreach ($students as $index => $student) {
            DB::table('student_club_memberships')->updateOrInsert(['student_club_id' => $clubs[$index % $clubs->count()]->id, 'student_id' => $student->id], ['school_id' => $school->id, 'assigned_by' => $admin->id, 'created_at' => now(), 'updated_at' => now()]);
        }

        DB::table('homework_assignments')->updateOrInsert(
            ['school_id' => $school->id, 'title' => 'Fractions in everyday life'],
            ['term_id' => $term->id, 'teacher_id' => $classTeacher->id, 'school_class_id' => $primaryFive->id, 'stream_id' => null, 'subject_id' => $subjects['Mathematics']->id, 'instructions' => 'Complete the five fraction exercises and explain one real-life example.', 'maximum_score' => 20, 'due_at' => now()->addDays(5), 'published_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()],
        );

        foreach ([['Inter-house Sports Day', 5, 'sports'], ['Parent and Teacher Meeting', 12, 'meeting']] as [$title, $days, $type]) {
            DB::table('school_events')->updateOrInsert(['school_id' => $school->id, 'title' => $title], ['term_id' => $term->id, 'event_date' => now()->addDays($days)->toDateString(), 'type' => $type, 'target_audience' => 'all', 'description' => 'Demo calendar event for the school community.', 'created_at' => now(), 'updated_at' => now()]);
        }

        foreach ([['Monday', '08:00:00', '09:00:00', 'Mathematics'], ['Tuesday', '09:00:00', '10:00:00', 'English'], ['Wednesday', '10:30:00', '11:30:00', 'Science']] as [$day, $start, $end, $subject]) {
            DB::table('timetable_slots')->updateOrInsert(
                ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $primaryFive->id, 'day_of_week' => $day, 'starts_at' => $start],
                ['stream_id' => null, 'subject_id' => $subjects[$subject]->id, 'user_id' => $classTeacher->id, 'ends_at' => $end, 'label' => $subject, 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function teacher(
        School $school,
        Designation $designation,
        string $name,
        string $email,
        string $staffNumber,
    ): User {
        $teacher = User::updateOrCreate(
            ['school_id' => $school->id, 'email' => $email],
            [
                'designation_id' => $designation->id,
                'name' => $name,
                'password' => 'TeacherTest@2026',
                'role' => 'teacher',
                'staff_number' => $staffNumber,
                'employment_status' => 'active',
                'joined_at' => now()->subYear()->toDateString(),
            ],
        );
        $teacher->forceFill(['email_verified_at' => now()])->save();

        return $teacher;
    }

    private function account(
        School $school,
        string $name,
        string $email,
        string $role,
        ?Designation $designation = null,
        ?string $staffNumber = null,
    ): User {
        $user = User::updateOrCreate(
            ['school_id' => $school->id, 'email' => $email],
            [
                'designation_id' => $designation?->id,
                'name' => $name,
                'password' => DemoAccounts::password(),
                'role' => $role,
                'staff_number' => $staffNumber,
                'employment_status' => 'active',
                'joined_at' => $staffNumber ? now()->subYear()->toDateString() : null,
            ],
        );
        $user->forceFill(['email_verified_at' => now()])->save();

        return $user;
    }
}
