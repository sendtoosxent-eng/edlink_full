<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\FeePayment;
use App\Models\FeeStructure;
use App\Models\School;
use App\Models\Stream;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\Subject;
use App\Models\Term;
use App\Services\SchoolAcademicSetup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReportingDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('is_demo', true)->latest('id')->firstOrFail();
        $school->update(['school_type' => 'secondary']);
        SchoolAcademicSetup::provision($school);

        $class = $school->classes()->where('education_stage', 'lower_secondary')->orderBy('sort_order')->firstOrFail();
        $stream = Stream::firstOrCreate(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'North']);
        $category = StudentCategory::firstOrCreate(['school_id' => $school->id, 'name' => 'Day Scholar']);
        $term = $school->currentTerm() ?? Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => now()->year, 'is_current' => true, 'status' => 'open']);
        $term->update(['is_current' => true, 'status' => 'open', 'locked' => false, 'closed_at' => null]);

        $fee = FeeStructure::updateOrCreate(
            ['school_id' => $school->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id],
            ['amount' => 850000],
        );

        $learners = [
            ['REPORT-001', 'Amina Nansubuga', 'female', 92, 410000],
            ['REPORT-002', 'Brian Okello', 'male', 84, 850000],
            ['REPORT-003', 'Cathy Namukasa', 'female', 73, 600000],
            ['REPORT-004', 'Daniel Mugisha', 'male', 64, 300000],
            ['REPORT-005', 'Esther Achieng', 'female', 53, 500000],
            ['REPORT-006', 'Frank Ssemanda', 'male', 37, 150000],
        ];
        $subjects = ['English', 'Mathematics', 'Biology', 'Chemistry', 'Physics', 'Geography', 'History', 'Entrepreneurship'];

        $exam = Exam::updateOrCreate(
            ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'stream_id' => $stream->id, 'name' => 'Reporting Demo Examination'],
            ['status' => 'published', 'published_at' => now()],
        );

        foreach ($subjects as $index => $name) {
            $subject = Subject::firstOrCreate(['school_id' => $school->id, 'name' => $name], ['code' => strtoupper(substr($name, 0, 3))]);
            DB::table('exam_papers')->updateOrInsert(
                ['exam_id' => $exam->id, 'subject_id' => $subject->id],
                ['maximum_score' => 100, 'weighting' => 1, 'created_at' => now(), 'updated_at' => now()],
            );
            $paperId = DB::table('exam_papers')->where(['exam_id' => $exam->id, 'subject_id' => $subject->id])->value('id');
            DB::table('exam_paper_submissions')->updateOrInsert(['exam_paper_id' => $paperId], ['status' => 'approved', 'submitted_at' => now(), 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

            foreach ($learners as $learnerIndex => [$admission, $name, $gender, $baseScore]) {
                $student = $this->student($school, $term, $class, $stream, $category, $fee, $admission, $name, $gender);
                $variation = (($index * 3 + $learnerIndex * 2) % 9) - 4;
                DB::table('exam_marks')->updateOrInsert(['exam_paper_id' => $paperId, 'student_id' => $student->id], ['score' => max(0, min(100, $baseScore + $variation)), 'created_at' => now(), 'updated_at' => now()]);
            }
        }

        foreach ($learners as $learnerIndex => [$admission, $name, $gender, $baseScore, $paid]) {
            $student = $this->student($school, $term, $class, $stream, $category, $fee, $admission, $name, $gender);
            foreach (range(0, 9) as $day) {
                AttendanceRecord::updateOrCreate(
                    ['school_id' => $school->id, 'term_id' => $term->id, 'student_id' => $student->id, 'attendance_date' => now()->subDays($day)->toDateString(), 'session_key' => 'daily'],
                    ['school_class_id' => $class->id, 'stream_id' => $stream->id, 'status' => ($day + $learnerIndex) % 8 === 0 ? 'absent' : (($day + $learnerIndex) % 6 === 0 ? 'late' : 'present')],
                );
            }
            FeePayment::updateOrCreate(
                ['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'transaction_id' => 'REPORT-PAY-'.$student->id],
                ['amount' => $paid, 'method' => 'mobile_money', 'paid_at' => now()->subDays(2), 'notes' => 'Reporting demo payment'],
            );
        }

        $this->command?->info("Reporting data seeded for {$school->name}: 6 learners, 8 subjects, approved marks, attendance and fees in {$class->name} North.");
    }

    private function student(School $school, Term $term, $class, Stream $stream, StudentCategory $category, FeeStructure $fee, string $admission, string $name, string $gender): Student
    {
        $student = Student::updateOrCreate(
            ['school_id' => $school->id, 'admission_no' => $admission],
            ['school_class_id' => $class->id, 'stream_id' => $stream->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'status' => 'active', 'name' => $name, 'gender' => $gender, 'admission_date' => now()->subMonths(5)->toDateString()],
        );
        StudentEnrolment::updateOrCreate(
            ['student_id' => $student->id, 'term_id' => $term->id],
            ['school_id' => $school->id, 'school_class_id' => $class->id, 'stream_id' => $stream->id, 'student_category_id' => $category->id, 'fee_structure_id' => $fee->id, 'base_fee_amount' => $fee->amount, 'status' => 'active', 'enrolled_at' => now()->subMonths(5)->toDateString()],
        );
        return $student;
    }
}
