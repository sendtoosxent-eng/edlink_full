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
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class TeacherSubjectVisibilitySeeder extends Seeder
{
    public function run(): void
    {
        $school = School::updateOrCreate(
            ['school_number' => 'EDL-TEACH'],
            [
                'name' => 'Teacher Visibility Demo School',
                'slug' => 'teacher-visibility-demo',
                'school_type' => 'primary',
                'status' => 'active',
                'license_plan' => 'premium',
                'license_status' => 'active',
                'license_started_at' => now()->subDay(),
                'license_expires_at' => now()->addYear(),
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

        $this->command?->info('Teacher subject-visibility data seeded for EDL-TEACH.');
        $this->command?->line('Class teacher: class.teacher@edlink.local / TeacherTest@2026');
        $this->command?->line('Subject teacher: subject.teacher@edlink.local / TeacherTest@2026');
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
}
