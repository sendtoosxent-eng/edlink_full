<?php

namespace Database\Seeders;

use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\SchoolGroup;
use App\Models\SchoolSetting;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\SchoolAcademicSetup;
use App\Support\DemoAccounts;
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PublicDemoSchoolsSeeder extends Seeder
{
    private const CATALOGUE = [
        'kindergarten' => [
            'database_type' => 'kindergarten', 'group' => 'Little Explorers Kindergarten', 'code' => 'DEMO-KG',
            'branches' => [['EDL-KINDER', 'Ntinda Main Campus'], ['EDL-KINDER-2', 'Kira Garden Campus']],
            'subjects' => [['Early Literacy', 'LIT'], ['Early Numeracy', 'NUM'], ['Creative Arts', 'ART'], ['Nature Discovery', 'NAT'], ['Music and Movement', 'MUS']],
            'categories' => ['Day Care', 'Full Day'], 'fee' => 480000,
        ],
        'primary' => [
            'database_type' => 'primary', 'group' => 'Edlink Primary Academy', 'code' => 'DEMO-PRI',
            'branches' => [[DemoAccounts::SCHOOL_NUMBER, 'Kampala Main Campus'], ['EDL-PRIMARY-2', 'Entebbe Lakeside Campus']],
            'subjects' => [['English', 'ENG'], ['Mathematics', 'MATH'], ['Science', 'SCI'], ['Social Studies', 'SST'], ['Religious Education', 'RE']],
            'categories' => ['Day Scholar', 'Boarding'], 'fee' => 525000,
        ],
        'secondary' => [
            'database_type' => 'secondary', 'group' => 'Horizon Secondary School', 'code' => 'DEMO-SEC',
            'branches' => [['EDL-SECOND', 'Makindye Main Campus'], ['EDL-SECOND-2', 'Mukono STEM Campus']],
            'subjects' => [['English Language', 'ENG'], ['Mathematics', 'MATH'], ['Biology', 'BIO'], ['Chemistry', 'CHEM'], ['Physics', 'PHY'], ['History', 'HIST'], ['Geography', 'GEO'], ['Entrepreneurship', 'ENT']],
            'categories' => ['Day Scholar', 'Boarding'], 'fee' => 850000,
        ],
        'vocational' => [
            'database_type' => 'tertiary', 'group' => 'Summit Vocational Institute', 'code' => 'DEMO-VOC',
            'branches' => [['EDL-VOCAT', 'Nakawa Skills Campus'], ['EDL-VOCAT-2', 'Namanve Technical Campus']],
            'subjects' => [['Electrical Installation', 'ELEC'], ['Motor Vehicle Technology', 'MVT'], ['Building Practice', 'BLD'], ['Fashion and Design', 'FAD'], ['Computer Applications', 'ICT'], ['Entrepreneurship', 'ENT']],
            'categories' => ['Government Sponsored', 'Private'], 'fee' => 720000,
        ],
    ];

    public function run(): void
    {
        // Keep the detailed original primary demonstration and make it the
        // first branch in the richer multi-school catalogue.
        $this->call(TeacherSubjectVisibilitySeeder::class);

        foreach (self::CATALOGUE as $type => $definition) {
            $group = SchoolGroup::updateOrCreate(
                ['code' => $definition['code']],
                ['name' => $definition['group'], 'status' => 'active'],
            );

            $schools = collect($definition['branches'])->map(function (array $branch, int $index) use ($type, $definition, $group) {
                [$number, $branchName] = $branch;
                $school = School::updateOrCreate(
                    ['school_number' => $number],
                    [
                        'school_group_id' => $group->id,
                        'name' => $definition['group'],
                        'branch_name' => $branchName,
                        'slug' => 'public-demo-'.$type.'-'.($index + 1),
                        'school_type' => $definition['database_type'],
                        'status' => 'active',
                        'license_plan' => 'premium',
                        'license_status' => 'active',
                        'license_started_at' => now()->subMonth(),
                        'license_expires_at' => now()->addYears(2),
                        'is_demo' => true,
                        'demo_expires_at' => now()->addYears(2),
                        'motto' => $this->motto($type),
                    ],
                );

                SchoolAcademicSetup::provision($school);

                return $school;
            });

            $home = $schools->first();
            $accounts = $this->ensureAccounts($home, $type);

            foreach ($schools as $branchIndex => $school) {
                $designations = $this->designations($school);
                $this->grantBranchAccess($accounts, $school, $designations);

                // The original primary branch already contains the deepest
                // hand-crafted dataset; do not overwrite it with generic data.
                if ($type !== 'primary' || $branchIndex !== 0) {
                    $this->seedBranch($school, $type, $definition, $accounts, $designations, $branchIndex);
                }

                SchoolSetting::setValue($school->id, 'public_demo_seed_version', DemoAccounts::SEED_VERSION);
                SchoolSetting::setValue($school->id, 'public_demo_type', $type);
            }
        }

        $this->command?->info('Four public demo school types with two branches each are ready.');
    }

    private function ensureAccounts(School $school, string $type): array
    {
        $designations = $this->designations($school);
        $roleMap = [
            'administrator' => ['admin', null],
            'class-teacher' => ['teacher', $designations['Class Teacher']],
            'subject-teacher' => ['teacher', $designations['Subject Teacher']],
            'bursar' => ['bursar', $designations['Bursar']],
            'parent' => ['parent', null],
            'student' => ['student', null],
        ];

        return collect(DemoAccounts::roles())->mapWithKeys(function (array $account, string $key) use ($school, $type, $roleMap) {
            [$role, $designation] = $roleMap[$key];
            $user = User::updateOrCreate(
                ['school_id' => $school->id, 'email' => $account['email']],
                [
                    'name' => $account['label'].' · '.DemoAccounts::schoolType($type)['label'],
                    'password' => DemoAccounts::password(),
                    'role' => $role,
                    'designation_id' => $designation?->id,
                    'staff_number' => in_array($role, ['admin', 'teacher', 'bursar'], true) ? strtoupper(substr($type, 0, 3)).'-'.strtoupper(substr($key, 0, 4)).'-01' : null,
                    'employment_status' => 'active',
                    'joined_at' => in_array($role, ['admin', 'teacher', 'bursar'], true) ? now()->subYears(2)->toDateString() : null,
                ],
            );
            $user->forceFill(['email_verified_at' => now()])->save();

            return [$key => $user];
        })->all();
    }

    private function designations(School $school): array
    {
        return collect(['Class Teacher', 'Subject Teacher', 'Bursar'])->mapWithKeys(fn (string $name) => [
            $name => Designation::updateOrCreate(
                ['school_id' => $school->id, 'name' => $name],
                ['permissions' => DesignationPermissions::defaults()[$name]],
            ),
        ])->all();
    }

    private function grantBranchAccess(array $accounts, School $school, array $designations): void
    {
        foreach ($accounts as $key => $user) {
            $role = match ($key) {
                'administrator' => 'admin', 'class-teacher', 'subject-teacher' => 'teacher',
                'bursar' => 'bursar', default => $key,
            };
            $designation = match ($key) {
                'class-teacher' => $designations['Class Teacher']->id,
                'subject-teacher' => $designations['Subject Teacher']->id,
                'bursar' => $designations['Bursar']->id,
                default => null,
            };
            $user->schoolAccesses()->syncWithoutDetaching([
                $school->id => ['role' => $role, 'designation_id' => $designation, 'can_view_group' => $key === 'administrator'],
            ]);
        }
    }

    private function seedBranch(School $school, string $type, array $definition, array $accounts, array $designations, int $branchIndex): void
    {
        foreach ([
            ['Academic Coordinator', 'academic.coordinator', $designations['Subject Teacher']],
            ['Demo Class Mentor', 'class.mentor', $designations['Class Teacher']],
            ['Admissions Officer', 'admissions', null],
            ['School Nurse', 'nurse', null],
        ] as $staffIndex => [$name, $emailPrefix, $designation]) {
            $staff = User::updateOrCreate(
                ['school_id' => $school->id, 'email' => $emailPrefix.'.'.$school->school_number.'@edlink.local'],
                ['name' => $name, 'password' => DemoAccounts::password(), 'role' => 'teacher', 'designation_id' => $designation?->id, 'staff_number' => $school->school_number.'-STAFF-'.($staffIndex + 1), 'employment_status' => 'active', 'joined_at' => now()->subYear()->toDateString()],
            );
            $staff->forceFill(['email_verified_at' => now()])->save();
        }

        $term = Term::updateOrCreate(
            ['school_id' => $school->id, 'year' => now()->year, 'term_number' => 1],
            ['name' => $type === 'vocational' ? 'Semester 1' : 'Term 1', 'is_current' => true, 'status' => 'open', 'locked' => false],
        );
        $classes = SchoolClass::where('school_id', $school->id)->orderBy('sort_order')->get();
        $subjects = collect($definition['subjects'])->mapWithKeys(fn (array $subject) => [
            $subject[0] => Subject::updateOrCreate(['school_id' => $school->id, 'name' => $subject[0]], ['code' => $subject[1]]),
        ]);
        $categories = collect($definition['categories'])->mapWithKeys(fn (string $name) => [
            $name => StudentCategory::firstOrCreate(['school_id' => $school->id, 'name' => $name]),
        ]);

        foreach ($classes as $classIndex => $class) {
            $streamNames = $type === 'kindergarten' ? ['Sunshine', 'Rainbow'] : ($type === 'vocational' ? ['Morning', 'Weekend'] : ['East', 'West']);
            $streams = collect($streamNames)->map(fn (string $name) => Stream::firstOrCreate([
                'school_id' => $school->id, 'school_class_id' => $class->id, 'name' => $name,
            ]));
            $class->update(['class_teacher_user_id' => $accounts['class-teacher']->id]);

            foreach ($subjects as $subject) {
                DB::table('class_subjects')->updateOrInsert(
                    ['term_id' => $term->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id],
                    ['school_id' => $school->id, 'created_at' => now(), 'updated_at' => now()],
                );
                DB::table('staff_subjects')->updateOrInsert(
                    ['school_id' => $school->id, 'term_id' => $term->id, 'user_id' => $accounts['subject-teacher']->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id],
                    ['created_at' => now(), 'updated_at' => now()],
                );
            }

            foreach ($categories->values() as $categoryIndex => $category) {
                FeeStructure::updateOrCreate(
                    ['school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id],
                    ['school_id' => $school->id, 'amount' => $definition['fee'] + ($classIndex * 35000) + ($categoryIndex * 180000)],
                );
            }

            foreach (range(1, 16) as $number) {
                $category = $categories->values()[$number % $categories->count()];
                $stream = $streams[$number % $streams->count()];
                $fee = FeeStructure::where(['school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id])->firstOrFail();
                $student = Student::updateOrCreate(
                    ['school_id' => $school->id, 'admission_no' => sprintf('%s%d-%03d', strtoupper(substr($type, 0, 1)), $classIndex + 1, $number)],
                    [
                        'school_class_id' => $class->id, 'stream_id' => $stream->id, 'student_category_id' => $category->id,
                        'term_id' => $term->id, 'name' => $this->learnerName($number, $classIndex, $branchIndex),
                        'status' => 'active', 'gender' => $number % 2 ? 'female' : 'male',
                        'admission_date' => now()->subYear()->startOfYear()->addDays($number)->toDateString(),
                    ],
                );
                StudentEnrolment::updateOrCreate(
                    ['student_id' => $student->id, 'term_id' => $term->id],
                    [
                        'school_id' => $school->id, 'school_class_id' => $class->id, 'stream_id' => $stream->id,
                        'student_category_id' => $category->id, 'fee_structure_id' => $fee->id, 'base_fee_amount' => $fee->amount,
                        'status' => 'active', 'enrolled_at' => now()->startOfYear()->addWeeks(3)->toDateString(), 'notes' => 'Public demo learner.',
                    ],
                );
                foreach (range(0, 5) as $daysAgo) {
                    $date = now()->subDays($daysAgo);
                    if (! $date->isWeekend()) {
                        DB::table('attendance_records')->updateOrInsert(
                            ['student_id' => $student->id, 'attendance_date' => $date->toDateString()],
                            ['school_id' => $school->id, 'term_id' => $term->id, 'status' => ($number === 4 && $daysAgo === 2) ? 'late' : 'present', 'recorded_by' => $accounts['class-teacher']->id, 'created_at' => now(), 'updated_at' => now()],
                        );
                    }
                }
            }

            $exam = Exam::updateOrCreate(
                ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => $type === 'vocational' ? 'Practical Competency Assessment' : 'Beginning of Term Assessment'],
                ['stream_id' => null, 'status' => 'published'],
            );
            foreach ($subjects->values()->take(4) as $paperIndex => $subject) {
                $paper = ExamPaper::updateOrCreate(['exam_id' => $exam->id, 'subject_id' => $subject->id], ['maximum_score' => 100, 'weighting' => 1]);
                DB::table('exam_paper_submissions')->updateOrInsert(['exam_paper_id' => $paper->id], ['status' => 'approved', 'submitted_by' => $accounts['subject-teacher']->id, 'submitted_at' => now()->subDay(), 'approved_by' => $accounts['administrator']->id, 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                foreach (Student::where('school_id', $school->id)->where('school_class_id', $class->id)->get() as $studentIndex => $student) {
                    DB::table('exam_marks')->updateOrInsert(['exam_paper_id' => $paper->id, 'student_id' => $student->id], ['score' => 62 + (($studentIndex + $paperIndex * 3) % 34), 'entered_by' => $accounts['subject-teacher']->id, 'created_at' => now(), 'updated_at' => now()]);
                }
            }
        }

        $firstClass = $classes->first();
        $firstSubject = $subjects->first();
        $learner = Student::where('school_id', $school->id)->orderBy('id')->firstOrFail();
        $accounts['parent']->portalStudents()->syncWithoutDetaching([$learner->id => ['school_id' => $school->id, 'relationship' => 'Guardian']]);
        $accounts['student']->portalStudents()->syncWithoutDetaching([$learner->id => ['school_id' => $school->id, 'relationship' => 'Student']]);
        $learner->guardians()->updateOrCreate(['email' => $accounts['parent']->email], ['name' => $accounts['parent']->name, 'relationship' => 'Guardian', 'is_primary' => true]);

        DB::table('homework_assignments')->updateOrInsert(
            ['school_id' => $school->id, 'title' => $type === 'vocational' ? 'Workshop safety inspection' : 'Community learning activity'],
            ['term_id' => $term->id, 'teacher_id' => $accounts['class-teacher']->id, 'school_class_id' => $firstClass->id, 'stream_id' => null, 'subject_id' => $firstSubject->id, 'instructions' => 'Complete the activity and submit your observations.', 'maximum_score' => 20, 'due_at' => now()->addDays(5), 'published_at' => now()->subDay(), 'created_at' => now(), 'updated_at' => now()],
        );
        foreach ([['Open Day', 2, 'community'], ['Branch Sports and Skills Festival', 9, 'sports']] as [$title, $days, $eventType]) {
            DB::table('school_events')->updateOrInsert(['school_id' => $school->id, 'title' => $title], ['term_id' => $term->id, 'event_date' => now()->addDays($days)->toDateString(), 'type' => $eventType, 'target_audience' => 'all', 'description' => 'A populated public demo event.', 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ([['Monday', '08:00:00', '09:00:00'], ['Wednesday', '10:00:00', '11:00:00'], ['Friday', '14:00:00', '15:00:00']] as $slotIndex => [$day, $start, $end]) {
            $subject = $subjects->values()[$slotIndex % $subjects->count()];
            DB::table('timetable_slots')->updateOrInsert(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $firstClass->id, 'day_of_week' => $day, 'starts_at' => $start], ['stream_id' => null, 'subject_id' => $subject->id, 'user_id' => $accounts['class-teacher']->id, 'ends_at' => $end, 'label' => $subject->name, 'created_at' => now(), 'updated_at' => now()]);
        }
        DB::table('school_notifications')->updateOrInsert(['school_id' => $school->id, 'user_id' => null, 'title' => 'Welcome to '.$school->branch_name], ['message' => 'Explore this populated branch, then use the branch switcher to compare its sister campus.', 'type' => 'info', 'read_at' => null, 'created_at' => now()->subMinutes(30), 'updated_at' => now()]);
    }

    private function learnerName(int $number, int $classIndex, int $branchIndex): string
    {
        $first = ['Amina', 'Brian', 'Cynthia', 'Daniel', 'Esther', 'Farid', 'Grace', 'Henry', 'Imani', 'Joel', 'Kato', 'Lydia', 'Martha', 'Noah', 'Olivia', 'Peter'];
        $last = ['Nakato', 'Okello', 'Nabirye', 'Kato', 'Atim', 'Ochieng', 'Namukasa', 'Mugisha', 'Akello', 'Ssemanda'];

        return $first[($number + $classIndex + $branchIndex) % count($first)].' '.$last[(($number * 2) + $classIndex + $branchIndex) % count($last)];
    }

    private function motto(string $type): string
    {
        return match ($type) {
            'kindergarten' => 'Curious minds, joyful beginnings',
            'primary' => 'Learn, grow and lead',
            'secondary' => 'Knowledge, character and ambition',
            default => 'Skills for work, confidence for life',
        };
    }
}
