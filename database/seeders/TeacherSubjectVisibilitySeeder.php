<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\FeeStructure;
use App\Models\GraduationRecord;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
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
                'name' => 'Edlink Primary Demo School',
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

        $classes = collect(['One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven'])
            ->mapWithKeys(function (string $level, int $index) use ($school) {
                $class = SchoolClass::updateOrCreate(
                    ['school_id' => $school->id, 'name' => 'Primary '.$level],
                    [
                        'education_stage' => 'primary',
                        'sort_order' => $index + 1,
                        'is_graduating_class' => $level === 'Seven',
                    ],
                );

                return [$class->name => $class];
            });
        $primaryFive = $classes['Primary Five'];
        $primarySix = $classes['Primary Six'];
        $primarySeven = $classes['Primary Seven'];

        $subjects = collect([
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'Mathematics', 'code' => 'MATH'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'Social Studies', 'code' => 'SST'],
            ['name' => 'Religious Education', 'code' => 'RE'],
            ['name' => 'Literacy', 'code' => 'LIT'],
            ['name' => 'Local Language', 'code' => 'LUG'],
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

        $classTeachers = collect([
            'Primary One' => ['Mary Akello', 'mary.akello@edlink.local', 'TEACH-CLASS-11'],
            'Primary Two' => ['John Ssemanda', 'john.ssemanda@edlink.local', 'TEACH-CLASS-12'],
            'Primary Three' => ['Ruth Nanyonjo', 'ruth.nanyonjo@edlink.local', 'TEACH-CLASS-13'],
            'Primary Four' => ['Peter Odongo', 'peter.odongo@edlink.local', 'TEACH-CLASS-14'],
            'Primary Five' => [$classTeacher->name, $classTeacher->email, $classTeacher->staff_number],
            'Primary Six' => ['Sarah Namukasa', 'sarah.namukasa@edlink.local', 'TEACH-CLASS-16'],
            'Primary Seven' => ['David Mugisha', 'david.mugisha@edlink.local', 'TEACH-CLASS-17'],
        ])->mapWithKeys(function (array $details, string $className) use ($school, $classTeacherDesignation, $classTeacher) {
            $teacher = $className === 'Primary Five'
                ? $classTeacher
                : $this->teacher($school, $classTeacherDesignation, $details[0], $details[1], $details[2]);

            return [$className => $teacher];
        });

        foreach ($classes as $className => $class) {
            $class->update(['class_teacher_user_id' => $classTeachers[$className]->id]);
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

        $subjectTeachers = collect([
            'Mathematics' => $subjectTeacher,
            'English' => $this->teacher($school, $subjectTeacherDesignation, 'Florence Nabirye', 'florence.nabirye@edlink.local', 'TEACH-SUBJECT-02'),
            'Science' => $this->teacher($school, $subjectTeacherDesignation, 'Michael Ochieng', 'michael.ochieng@edlink.local', 'TEACH-SUBJECT-03'),
            'Social Studies' => $this->teacher($school, $subjectTeacherDesignation, 'Agnes Tumusiime', 'agnes.tumusiime@edlink.local', 'TEACH-SUBJECT-04'),
        ]);
        foreach ($subjectTeachers as $subjectName => $teacher) {
            foreach ($classes as $class) {
                DB::table('staff_subjects')->updateOrInsert(
                    [
                        'school_id' => $school->id,
                        'term_id' => $term->id,
                        'user_id' => $teacher->id,
                        'school_class_id' => $class->id,
                        'subject_id' => $subjects[$subjectName]->id,
                    ],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }
        }

        $categories = collect(['Day Scholar', 'Boarding'])
            ->mapWithKeys(fn (string $name) => [$name => StudentCategory::firstOrCreate(['school_id' => $school->id, 'name' => $name])]);
        foreach ($classes->values() as $index => $class) {
            foreach ($categories as $categoryName => $category) {
                FeeStructure::updateOrCreate(
                    ['school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id],
                    [
                        'school_id' => $school->id,
                        'amount' => 425000 + (($index + 1) * 25000) + ($categoryName === 'Boarding' ? 650000 : 0),
                    ],
                );
            }
        }

        $firstNames = ['Amina', 'Brian', 'Cathy', 'David', 'Esther', 'Frank', 'Gloria', 'Henry', 'Irene', 'Joel', 'Karen', 'Liam', 'Mercy', 'Nathan', 'Olivia', 'Peter', 'Queen', 'Robert', 'Sarah', 'Timothy', 'Unity', 'Victor', 'Winnie', 'Yasin', 'Zahara'];
        $surnames = ['Nakato', 'Okello', 'Atim', 'Kato', 'Namusoke', 'Ochieng', 'Nabirye', 'Tumusiime', 'Akello', 'Ssemanda', 'Nanyonjo', 'Odongo', 'Nakitende', 'Mugisha', 'Auma', 'Kisembo', 'Namukasa', 'Wasswa', 'Nansubuga', 'Opio'];

        foreach ($classes->values() as $classIndex => $class) {
            $prefix = 'P'.($classIndex + 1);
            $nameOffset = $classIndex * 3;
            foreach (range(1, 50) as $number) {
                $admissionNumber = sprintf('%s-%03d', $prefix, $number);
                $name = match ([$prefix, $number]) {
                    ['P5', 1] => 'Amina Class Learner',
                    ['P6', 1] => 'Brian Subject Learner',
                    default => $firstNames[($number + $nameOffset - 1) % count($firstNames)].' '.$surnames[(($number * 3) + $nameOffset) % count($surnames)],
                };

                $category = $number % 5 === 0 ? $categories['Boarding'] : $categories['Day Scholar'];
                $feeStructure = FeeStructure::where([
                    'school_class_id' => $class->id,
                    'student_category_id' => $category->id,
                    'term_id' => $term->id,
                ])->firstOrFail();
                $student = Student::updateOrCreate(
                    ['school_id' => $school->id, 'admission_no' => $admissionNumber],
                    [
                        'school_class_id' => $class->id,
                        'student_category_id' => $category->id,
                        'term_id' => $term->id,
                        'name' => $name,
                        'status' => 'active',
                        'gender' => $number % 2 === 0 ? 'male' : 'female',
                        'admission_date' => now()->subYears(2)->startOfYear()->addDays($number)->toDateString(),
                    ],
                );

                StudentEnrolment::updateOrCreate(
                    ['student_id' => $student->id, 'term_id' => $term->id],
                    [
                        'school_id' => $school->id,
                        'school_class_id' => $class->id,
                        'stream_id' => null,
                        'student_category_id' => $category->id,
                        'fee_structure_id' => $feeStructure->id,
                        'base_fee_amount' => $feeStructure->amount,
                        'status' => 'active',
                        'promotion_outcome' => null,
                        'enrolled_at' => $term->year.'-01-29',
                        'exited_at' => null,
                        'notes' => 'Seeded public demo learner.',
                    ],
                );
            }
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
        $this->seedGraduates($school, $primarySeven);
        SchoolSetting::setValue($school->id, 'public_demo_seed_version', DemoAccounts::SEED_VERSION);

        $this->command?->info('Teacher subject-visibility data seeded for EDL-TEACH.');
        $this->command?->line('All demo accounts use password: TeacherTest@2026');
    }

    private function seedGraduates(School $school, SchoolClass $graduatingClass): void
    {
        $admin = User::where('school_id', $school->id)->where('email', 'admin@edlink.local')->firstOrFail();
        $category = StudentCategory::firstOrCreate(['school_id' => $school->id, 'name' => 'Day Scholar']);

        foreach ([
            ['ALU-2025-001', 'Grace Namusoke', 2025, '2025-11-28', 86.40, 0],
            ['ALU-2025-002', 'Daniel Okello', 2025, '2025-11-28', 78.75, 45000],
            ['ALU-2025-003', 'Patricia Nabirye', 2025, '2025-11-28', 82.10, 0],
            ['ALU-2025-004', 'Samuel Ochieng', 2025, '2025-11-28', 69.85, 75000],
            ['ALU-2025-005', 'Rebecca Tumusiime', 2025, '2025-11-28', 88.30, 0],
            ['ALU-2024-001', 'Sharon Atim', 2024, '2024-11-29', 91.20, 0],
            ['ALU-2024-002', 'Isaac Mugisha', 2024, '2024-11-29', 76.45, 30000],
            ['ALU-2024-003', 'Joan Nakitende', 2024, '2024-11-29', 84.60, 0],
            ['ALU-2024-004', 'Andrew Odongo', 2024, '2024-11-29', 71.90, 95000],
            ['ALU-2023-001', 'Moses Kato', 2023, '2023-12-01', 73.50, 120000],
            ['ALU-2023-002', 'Lydia Namukasa', 2023, '2023-12-01', 80.25, 0],
            ['ALU-2023-003', 'Joseph Opio', 2023, '2023-12-01', 67.75, 60000],
        ] as [$admissionNumber, $name, $year, $graduatedAt, $average, $balance]) {
            $finalTerm = Term::updateOrCreate(
                ['school_id' => $school->id, 'year' => $year, 'term_number' => 3],
                [
                    'name' => 'Term 3',
                    'is_current' => false,
                    'status' => 'closed',
                    'locked' => true,
                    'closed_at' => $graduatedAt.' 17:00:00',
                ],
            );

            $student = Student::updateOrCreate(
                ['school_id' => $school->id, 'admission_no' => $admissionNumber],
                [
                    'school_class_id' => $graduatingClass->id,
                    'stream_id' => null,
                    'student_category_id' => $category->id,
                    'term_id' => $finalTerm->id,
                    'name' => $name,
                    'status' => 'graduated',
                    'admission_date' => ($year - 7).'-02-01',
                ],
            );

            StudentEnrolment::updateOrCreate(
                ['student_id' => $student->id, 'term_id' => $finalTerm->id],
                [
                    'school_id' => $school->id,
                    'school_class_id' => $graduatingClass->id,
                    'stream_id' => null,
                    'student_category_id' => $category->id,
                    'fee_structure_id' => null,
                    'base_fee_amount' => 0,
                    'status' => 'graduated',
                    'promotion_outcome' => 'graduated',
                    'enrolled_at' => $year.'-01-29',
                    'exited_at' => $graduatedAt,
                    'notes' => 'Seeded demo alumni record.',
                ],
            );

            GraduationRecord::updateOrCreate(
                ['student_id' => $student->id, 'term_id' => $finalTerm->id],
                [
                    'school_id' => $school->id,
                    'school_class_id' => $graduatingClass->id,
                    'graduation_year' => $year,
                    'graduated_at' => $graduatedAt,
                    'final_average' => $average,
                    'outstanding_balance' => $balance,
                    'certificate_number' => "EDL-DEMO-{$year}-{$student->id}",
                    'portal_access' => 'read_only',
                    'graduated_by' => $admin->id,
                    'reversed_at' => null,
                    'reversed_by' => null,
                    'reversal_reason' => null,
                ],
            );
        }
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
