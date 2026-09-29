<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class TeacherDashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('is_demo', true)->latest('id')->firstOrFail();
        $term = $school->currentTerm();
        $students = $school->students()->where('status', 'active')->orderBy('id')->get();

        if (! $term || $students->isEmpty()) {
            throw new \RuntimeException('Seed reporting demo data before teacher dashboard data.');
        }

        $classId = $students->first()->school_class_id;
        $streamId = $students->first()->stream_id;
        $students = $students->where('school_class_id', $classId)->take(8)->values();
        $subjects = Subject::where('school_id', $school->id)->orderBy('name')->take(3)->get();

        if ($subjects->count() < 3) {
            foreach (['English', 'Mathematics', 'Biology'] as $name) {
                Subject::firstOrCreate(['school_id' => $school->id, 'name' => $name], ['code' => strtoupper(substr($name, 0, 3))]);
            }
            $subjects = Subject::where('school_id', $school->id)->orderBy('name')->take(3)->get();
        }

        $designation = Designation::updateOrCreate(
            ['school_id' => $school->id, 'name' => 'Subject Teacher'],
            ['description' => 'Subject teaching, subject attendance, marks and teaching reports.', 'permissions' => DesignationPermissions::defaults()['Subject Teacher']],
        );

        $teacher = User::updateOrCreate(
            ['school_id' => $school->id, 'email' => 'teacher@demo.edlink.test'],
            [
                'designation_id' => $designation->id,
                'staff_number' => 'STF-TEACHER-DEMO',
                'name' => 'Demo Subject Teacher',
                'phone' => '+256 700 555 020',
                'job_title' => 'Science and Mathematics Teacher',
                'role' => 'teacher',
                'base_salary' => 1250000,
                'employment_status' => 'active',
                'joined_at' => now()->subYears(3)->toDateString(),
                'password' => Hash::make('password'),
            ],
        );
        $teacher->forceFill(['email_verified_at' => now()])->save();

        DB::transaction(function () use ($school, $term, $teacher, $students, $subjects, $classId, $streamId) {
            foreach ($subjects as $subject) {
                DB::table('staff_subjects')->updateOrInsert(
                    ['user_id' => $teacher->id, 'subject_id' => $subject->id, 'school_class_id' => $classId],
                    ['school_id' => $school->id, 'term_id' => $term->id, 'created_at' => now(), 'updated_at' => now()],
                );
                DB::table('class_subjects')->updateOrInsert(
                    ['term_id' => $term->id, 'school_class_id' => $classId, 'subject_id' => $subject->id],
                    ['school_id' => $school->id, 'created_at' => now(), 'updated_at' => now()],
                );
            }

            $weekdays = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];
            foreach ($weekdays as $dayIndex => $day) {
                foreach ($subjects as $subjectIndex => $subject) {
                    $hour = 8 + $subjectIndex;
                    DB::table('timetable_slots')->updateOrInsert(
                        [
                            'school_id' => $school->id, 'term_id' => $term->id, 'user_id' => $teacher->id,
                            'day_of_week' => $day, 'starts_at' => sprintf('%02d:00', $hour),
                        ],
                        [
                            'school_class_id' => $classId, 'stream_id' => $streamId, 'subject_id' => $subject->id,
                            'ends_at' => sprintf('%02d:40', $hour), 'label' => $subjectIndex === 0 ? 'Morning lesson' : 'Subject lesson',
                            'created_at' => now(), 'updated_at' => now(),
                        ],
                    );
                }
            }

            foreach (range(6, 0) as $dayOffset) {
                $date = now()->subDays($dayOffset)->toDateString();
                foreach ($subjects as $subjectIndex => $subject) {
                    foreach ($students as $studentIndex => $student) {
                        $status = ($studentIndex + $subjectIndex + $dayOffset) % 11 === 0 ? 'absent' : (($studentIndex + $dayOffset) % 7 === 0 ? 'late' : 'present');
                        AttendanceRecord::updateOrCreate(
                            ['student_id' => $student->id, 'attendance_date' => $date, 'session_key' => 'teacher-demo-'.$subject->id],
                            [
                                'school_id' => $school->id, 'term_id' => $term->id, 'subject_id' => $subject->id,
                                'school_class_id' => $classId, 'stream_id' => $streamId, 'lesson_time' => sprintf('%02d:00', 8 + $subjectIndex),
                                'status' => $status, 'recorded_by' => $teacher->id,
                            ],
                        );
                    }
                }
            }

            $exam = Exam::updateOrCreate(
                ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $classId, 'name' => 'Teacher Dashboard Practice'],
                ['stream_id' => $streamId, 'status' => 'draft'],
            );

            foreach ($subjects as $subjectIndex => $subject) {
                $paper = ExamPaper::updateOrCreate(
                    ['exam_id' => $exam->id, 'subject_id' => $subject->id],
                    ['maximum_score' => 100, 'weighting' => 1],
                );
                $submissionStatus = ['draft', 'rejected', 'approved'][$subjectIndex % 3];
                DB::table('exam_paper_submissions')->updateOrInsert(
                    ['exam_paper_id' => $paper->id],
                    [
                        'status' => $submissionStatus, 'submitted_by' => $teacher->id,
                        'submitted_at' => $submissionStatus === 'draft' ? null : now()->subDays(2),
                        'approved_by' => $submissionStatus === 'approved' ? $teacher->id : null,
                        'approved_at' => $submissionStatus === 'approved' ? now()->subDay() : null,
                        'created_at' => now(), 'updated_at' => now(),
                    ],
                );

                foreach ($students as $studentIndex => $student) {
                    DB::table('exam_marks')->updateOrInsert(
                        ['exam_paper_id' => $paper->id, 'student_id' => $student->id],
                        ['score' => 52 + (($studentIndex * 7 + $subjectIndex * 9) % 43), 'entered_by' => $teacher->id, 'created_at' => now(), 'updated_at' => now()],
                    );
                }
            }
        });

        $this->command?->info("Teacher dashboard demo data seeded for {$school->name}.");
        $this->command?->info('Login: teacher@demo.edlink.test / password');
        $this->command?->info('Created 3 subject assignments, a weekly timetable, 7 days of attendance, marks, and 2 pending mark sheets.');
    }
}