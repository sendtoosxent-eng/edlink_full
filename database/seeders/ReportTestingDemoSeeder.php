<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\ExamCategory;
use App\Models\ExamCategoryGroup;
use App\Models\ExamCategoryGroupAssignment;
use App\Models\ExamPaper;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportTestingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $schools = School::where('is_demo', true)->with('terms')->orderBy('id')->get();
        if ($schools->isEmpty()) {
            throw new \RuntimeException('Create the sample demo schools before running ReportTestingDemoSeeder.');
        }

        foreach ($schools as $school) {
            $term = $school->currentTerm();
            if (! $term) {
                continue;
            }

            DB::transaction(function () use ($school, $term): void {
                $months = [8 => 'August', 9 => 'September', 10 => 'October'];
                foreach ($months as $month => $monthName) {
                    Expense::updateOrCreate(
                        ['school_id' => $school->id, 'reference_number' => sprintf('REPORT-DEMO-EXP-%d-%02d', $term->year, $month)],
                        ['term_id' => $term->id, 'settlement_type' => 'immediate',
                            'category' => [8 => 'Supplies', 9 => 'Utilities', 10 => 'Maintenance'][$month],
                            'payee' => [8 => 'Demo Learning Supplies', 9 => 'Demo Utility Provider', 10 => 'Demo Maintenance Services'][$month],
                            'amount' => [8 => 280000, 9 => 420000, 10 => 350000][$month],
                            'description' => "Synthetic {$monthName} operating expense for dashboard testing.",
                            'expense_date' => sprintf('%d-%02d-15', $term->year, $month)],
                    );
                }

                $fallbackCategory = StudentCategory::firstOrCreate(
                    ['school_id' => $school->id, 'name' => 'Day Scholar'],
                );
                $enrolments = StudentEnrolment::with(['student', 'schoolClass'])
                    ->where('school_id', $school->id)->where('term_id', $term->id)
                    ->whereIn('status', ['active', 'pending'])->get();

                foreach ($enrolments->groupBy('school_class_id')->sortKeys()->take(1) as $classId => $classEnrolments) {
                    $classEnrolments = $classEnrolments->values()->take(8);
                    $schoolClass = $classEnrolments->first()?->schoolClass;
                    if (! $schoolClass) {
                        continue;
                    }
                    $group = ExamCategoryGroup::updateOrCreate(
                        ['school_id' => $school->id, 'term_id' => $term->id, 'name' => 'Demo '.$schoolClass->name.' Assessment'],
                    );
                    ExamCategoryGroupAssignment::updateOrCreate(
                        ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $schoolClass->id],
                        ['exam_category_group_id' => $group->id],
                    );
                    $coursework = ExamCategory::updateOrCreate(
                        ['school_id' => $school->id, 'term_id' => $term->id, 'exam_category_group_id' => $group->id, 'name' => 'Coursework'],
                        ['weight' => 40, 'sort_order' => 0],
                    );
                    $final = ExamCategory::updateOrCreate(
                        ['school_id' => $school->id, 'term_id' => $term->id, 'exam_category_group_id' => $group->id, 'name' => 'Final Examination'],
                        ['weight' => 60, 'sort_order' => 1],
                    );

                    $subjects = Subject::where('school_id', $school->id)
                        ->whereIn('id', DB::table('class_subjects')->where([
                            'school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $schoolClass->id,
                        ])->select('subject_id'))->orderBy('name')->take(6)->get();
                    if ($subjects->isEmpty()) {
                        $subjects = collect(['English', 'Mathematics', 'Science'])->map(function (string $name) use ($school, $schoolClass, $term) {
                            $subject = Subject::firstOrCreate(
                                ['school_id' => $school->id, 'name' => $name],
                                ['code' => strtoupper(substr($name, 0, 3))],
                            );
                            DB::table('class_subjects')->updateOrInsert(
                                ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $schoolClass->id, 'subject_id' => $subject->id],
                                ['created_at' => now(), 'updated_at' => now()],
                            );

                            return $subject;
                        });
                    }

                    foreach ([[$coursework, 'Demo Coursework'], [$final, 'Demo Final Examination']] as $examIndex => [$category, $examName]) {
                        $exam = Exam::updateOrCreate(
                            ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $schoolClass->id, 'stream_id' => null, 'name' => $examName],
                            ['exam_category_id' => $category->id, 'status' => 'published', 'published_at' => now()],
                        );
                        foreach ($subjects as $subjectIndex => $subject) {
                            $paper = ExamPaper::updateOrCreate(
                                ['exam_id' => $exam->id, 'subject_id' => $subject->id],
                                ['maximum_score' => 100, 'weighting' => $subjectIndex === 0 ? 1.5 : 1],
                            );
                            DB::table('exam_paper_submissions')->updateOrInsert(
                                ['exam_paper_id' => $paper->id],
                                ['status' => 'approved', 'submitted_at' => now()->subDay(), 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()],
                            );
                            foreach ($classEnrolments->values() as $studentIndex => $enrolment) {
                                $score = 52 + (($studentIndex * 7 + $subjectIndex * 5 + $examIndex * 11) % 44);
                                DB::table('exam_marks')->updateOrInsert(
                                    ['exam_paper_id' => $paper->id, 'student_id' => $enrolment->student_id],
                                    ['score' => $score, 'created_at' => now(), 'updated_at' => now()],
                                );
                            }
                        }
                    }

                    foreach ($classEnrolments->values() as $studentIndex => $enrolment) {
                        $categoryId = $enrolment->student_category_id ?: $fallbackCategory->id;
                        $fee = FeeStructure::updateOrCreate(
                            ['school_id' => $school->id, 'school_class_id' => $schoolClass->id, 'student_category_id' => $categoryId, 'term_id' => $term->id],
                            ['amount' => 600000 + ($schoolClass->sort_order * 25000)],
                        );
                        if (! $enrolment->student_category_id || ! $enrolment->fee_structure_id) {
                            $enrolment->update(['student_category_id' => $categoryId, 'fee_structure_id' => $fee->id, 'base_fee_amount' => $fee->amount]);
                        }
                        foreach ($months as $month => $monthName) {
                            $amount = [8 => 150000, 9 => 125000, 10 => 100000][$month] + (($studentIndex % 3) * 25000);
                            FeePayment::updateOrCreate(
                                ['school_id' => $school->id, 'student_id' => $enrolment->student_id, 'term_id' => $term->id,
                                    'transaction_id' => sprintf('REPORT-DEMO-%d-%02d-%d', $term->year, $month, $enrolment->student_id)],
                                ['amount' => $amount, 'method' => [8 => 'bank', 9 => 'mobile_money', 10 => 'cash'][$month],
                                    'paid_at' => sprintf('%d-%02d-%02d 10:00:00', $term->year, $month, 8 + ($studentIndex % 8)),
                                    'notes' => "Synthetic {$monthName} fee payment for dashboard testing."],
                            );
                        }

                        foreach (array_keys($months) as $month) {
                            foreach ([3, 7, 11, 15, 19, 23] as $dayIndex => $day) {
                                $date = now()->setDate($term->year, $month, $day)->startOfDay();
                                AttendanceRecord::updateOrCreate(
                                    ['school_id' => $school->id, 'term_id' => $term->id, 'student_id' => $enrolment->student_id,
                                        'attendance_date' => $date->toDateString(), 'session_key' => 'daily'],
                                    ['school_class_id' => $schoolClass->id, 'stream_id' => $enrolment->stream_id,
                                        'status' => ($studentIndex + $dayIndex + $month) % 11 === 0 ? 'absent' : (($studentIndex + $dayIndex) % 7 === 0 ? 'late' : 'present')],
                                );
                            }
                        }
                    }
                }
            });

            $this->command?->info("Report testing data seeded for {$school->name}: weighted exams plus August, September and October attendance, expenses and fee payments.");
        }
    }
}
