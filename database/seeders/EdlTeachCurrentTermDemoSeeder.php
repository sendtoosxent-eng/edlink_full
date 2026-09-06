<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\School;
use App\Models\Subject;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EdlTeachCurrentTermDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-TEACH')->where('is_demo', true)->firstOrFail();
        $term = $school->currentTerm();
        if (! $term || ! $term->isOpen() || $term->isLocked()) {
            throw new \RuntimeException('EDL-TEACH must have an open, unlocked current term.');
        }
        $students = $school->students()->where('status', 'active')->whereNotNull('school_class_id')->orderBy('id')->get();
        if ($students->isEmpty()) {
            throw new \RuntimeException('EDL-TEACH has no active learners with classes.');
        }

        DB::transaction(function () use ($school, $term, $students) {
            foreach ($students as $index => $student) {
                foreach (range(0, 6) as $offset) {
                    AttendanceRecord::firstOrCreate([
                        'student_id' => $student->id,
                        'attendance_date' => today()->subDays($offset)->toDateTimeString(),
                        'session_key' => 'app-demo-term-'.$term->id,
                    ], [
                        'school_id' => $school->id, 'term_id' => $term->id,
                        'school_class_id' => $student->school_class_id, 'stream_id' => $student->stream_id,
                        'status' => ['present', 'present', 'late', 'present', 'absent', 'present', 'present'][$offset],
                    ]);
                }
                foreach ([100000, 50000] as $paymentIndex => $amount) {
                    $reference = "APP-DEMO-{$term->id}-{$student->id}-{$paymentIndex}";
                    $payment = FeePayment::firstOrCreate([
                        'school_id' => $school->id, 'student_id' => $student->id,
                        'term_id' => $term->id, 'transaction_id' => $reference,
                    ], [
                        'amount' => $amount, 'method' => 'cash',
                        'notes' => 'Synthetic app demonstration payment; no money received.',
                        'paid_at' => now()->subDays($paymentIndex + 1),
                    ]);
                    // Synthetic demo ledger only: do not trigger receipt emails or real payment approval.
                    $entry = FinanceLedgerEntry::firstOrCreate([
                        'source_type' => FeePayment::class, 'source_id' => $payment->id,
                    ], [
                        'school_id' => $school->id, 'term_id' => $term->id,
                        'reference' => $reference, 'entry_type' => 'fee_payment', 'direction' => 'credit',
                        'amount' => $amount, 'description' => 'Synthetic app demonstration payment',
                        'status' => 'posted', 'posted_at' => $payment->paid_at,
                    ]);
                    if ($entry->status === 'pending') {
                        $entry->update(['status' => 'posted', 'posted_at' => $payment->paid_at]);
                    }
                }
            }
            foreach ($students->groupBy('school_class_id') as $classId => $learners) {
                $exam = Exam::firstOrCreate([
                    'school_id' => $school->id, 'term_id' => $term->id,
                    'school_class_id' => $classId, 'name' => 'App Demo Current Term Assessment',
                ], ['status' => 'published', 'published_at' => now()]);
                foreach (['Mathematics', 'English', 'Science'] as $subjectIndex => $name) {
                    $subject = Subject::firstOrCreate(['school_id' => $school->id, 'name' => $name], ['code' => strtoupper(substr($name, 0, 3))]);
                    $paper = ExamPaper::firstOrCreate(['exam_id' => $exam->id, 'subject_id' => $subject->id], ['maximum_score' => 100, 'weighting' => 1]);
                    DB::table('exam_paper_submissions')->updateOrInsert(['exam_paper_id' => $paper->id], ['status' => 'approved', 'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
                    foreach ($learners->values() as $index => $student) {
                        DB::table('exam_marks')->updateOrInsert(['exam_paper_id' => $paper->id, 'student_id' => $student->id], ['score' => 65 + (($index + $subjectIndex * 7) % 30), 'created_at' => now(), 'updated_at' => now()]);
                    }
                }
            }
        });
        $this->command?->info("EDL-TEACH: {$term->name}, {$term->year}; {$students->count()} learners. Each has 2 demo payments (UGX 150,000), 7 attendance records and 3 published subject scores. Reruns preserve payment counts.");
    }
}
