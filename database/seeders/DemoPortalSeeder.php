<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Exam;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class DemoPortalSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-03E09')->first() ?? School::where('is_demo', true)->first();
        if (! $school || ! $school->currentTerm() || ! $school->classes()->exists()) {
            $this->command?->warn('Demo portal seed skipped: the demo school needs an open term and at least one class.');
            return;
        }

        $term = $school->currentTerm();
        $class = $school->classes()->first();
        $category = StudentCategory::firstOrCreate(['school_id' => $school->id, 'name' => 'Day Scholar']);
        $student = Student::firstOrCreate(['school_id' => $school->id, 'admission_no' => 'DEMO-PORTAL-001'], [
            'school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'status' => 'active', 'name' => 'Amina Nakato', 'gender' => 'female', 'admission_date' => now()->toDateString(),
        ]);
        $student->update(['school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'status' => 'active']);
        DB::table('student_enrolments')->updateOrInsert(['student_id' => $student->id, 'term_id' => $term->id], ['school_id' => $school->id, 'school_class_id' => $class->id, 'stream_id' => $student->stream_id, 'student_category_id' => $category->id, 'fee_structure_id' => null, 'base_fee_amount' => 500000, 'status' => 'active', 'enrolled_at' => now()->toDateString(), 'updated_at' => now(), 'created_at' => now()]);

        $parent = User::updateOrCreate(['school_id' => $school->id, 'email' => 'oscarnsamba1@gmail.com'], ['name' => 'Oscar Nsamba', 'phone' => '+256700000101', 'role' => 'parent', 'password' => Hash::make('Password123!'), 'email_verified_at' => now()]);
        $learnerLogin = User::updateOrCreate(['school_id' => $school->id, 'email' => 'student.demo@edlink.test'], ['name' => $student->name, 'role' => 'student', 'password' => Hash::make('Password123!'), 'email_verified_at' => now()]);
        $parent->portalStudents()->syncWithoutDetaching([$student->id => ['school_id' => $school->id, 'relationship' => 'Guardian']]);
        $student->guardians()->updateOrCreate(['email' => $parent->email], ['name' => $parent->name, 'relationship' => 'Guardian', 'phone' => $parent->phone, 'is_primary' => true]);
        $learnerLogin->portalStudents()->syncWithoutDetaching([$student->id => ['school_id' => $school->id, 'relationship' => 'Student']]);

        foreach (range(0, 4) as $offset) AttendanceRecord::updateOrCreate(['student_id' => $student->id, 'attendance_date' => now()->subDays($offset)->startOfDay()->format('Y-m-d H:i:s'), 'session_key' => 'daily'], ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $student->school_class_id, 'stream_id' => $student->stream_id, 'status' => $offset === 1 ? 'absent' : ($offset === 3 ? 'late' : 'present'), 'recorded_by' => null]);
        $payment = FeePayment::firstOrCreate(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'transaction_id' => 'DEMO-MOMO-001'], ['amount' => 250000, 'method' => 'mobile_money', 'paid_at' => now()->subDays(2), 'notes' => 'Demo parent portal payment']);
        CashPoolEntry::firstOrCreate(['fee_payment_id' => $payment->id], ['school_id' => $school->id, 'term_id' => $term->id, 'direction' => 'credit', 'amount' => $payment->amount, 'description' => 'Demo portal payment', 'transacted_at' => $payment->paid_at]);

        $exam = Exam::firstOrCreate(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => 'Portal Demo Assessment'], ['status' => 'published', 'published_at' => now()]);
        $exam->update(['status' => 'published', 'published_at' => $exam->published_at ?? now()]);
        foreach (['Mathematics' => 78, 'English' => 84, 'Science' => 75] as $name => $score) {
            $subject = Subject::firstOrCreate(['school_id' => $school->id, 'name' => $name], ['code' => strtoupper(substr($name, 0, 3))]);
            DB::table('exam_papers')->updateOrInsert(['exam_id' => $exam->id, 'subject_id' => $subject->id], ['maximum_score' => 100, 'weighting' => 1, 'updated_at' => now(), 'created_at' => now()]);
            $paperId = DB::table('exam_papers')->where(['exam_id' => $exam->id, 'subject_id' => $subject->id])->value('id');
            DB::table('exam_marks')->updateOrInsert(['exam_paper_id' => $paperId, 'student_id' => $student->id], ['score' => $score, 'updated_at' => now(), 'created_at' => now()]);
            DB::table('exam_paper_submissions')->updateOrInsert(['exam_paper_id' => $paperId], ['status' => 'approved', 'approved_at' => now(), 'updated_at' => now(), 'created_at' => now()]);
        }
        $this->command?->info('Portal demo accounts created for Amina Nakato and parent oscarnsamba1@gmail.com.');
    }
}
