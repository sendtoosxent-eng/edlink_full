<?php

namespace Database\Seeders;

use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AnnualDashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-TEACH')->where('is_demo', true)->firstOrFail();
        $term = $school->currentTerm();
        $students = $school->students()->where('status', 'active')->whereNotNull('school_class_id')->orderBy('id')->take(4)->get();
        $recorder = User::where('school_id', $school->id)->whereIn('role', ['admin', 'bursar'])->orderByRaw("role = 'admin' desc")->first();

        if (! $term || $students->isEmpty() || ! $recorder) {
            throw new \RuntimeException('EDL-TEACH needs a current term, active learners and an administrator before annual dashboard data can be seeded.');
        }

        DB::transaction(function () use ($school, $term, $students, $recorder): void {
            foreach (range(1, now()->month) as $month) {
                $date = now()->setDate((int) $term->year, $month, 1)->startOfDay();
                $monthCode = $date->format('Ym');

                foreach ($students->take(2) as $index => $student) {
                    $payment = FeePayment::updateOrCreate(
                        ['school_id' => $school->id, 'transaction_id' => "ANNUAL-DEMO-PAY-{$monthCode}-{$student->id}"],
                        ['student_id' => $student->id, 'term_id' => $term->id, 'amount' => 120000 + ($month * 15000) + ($index * 35000),
                            'method' => $index ? 'mobile_money' : 'bank', 'notes' => 'Synthetic annual dashboard payment.',
                            'recorded_by' => $recorder->id, 'paid_at' => $date->copy()->addDays(5 + ($index * 8))],
                    );
                    $this->postSyntheticEntry($payment, 'fee_payment', 'credit', $payment->paid_at, $recorder->id);
                }

                $expense = Expense::updateOrCreate(
                    ['school_id' => $school->id, 'reference_number' => "ANNUAL-DEMO-EXP-{$monthCode}"],
                    ['term_id' => $term->id, 'settlement_type' => 'immediate', 'category' => ['Utilities', 'Supplies', 'Maintenance', 'Transport'][$month % 4],
                        'payee' => 'Annual Dashboard Demo Supplier', 'amount' => 85000 + ($month * 18000),
                        'description' => 'Synthetic monthly operating expense for dashboard demonstration.',
                        'expense_date' => $date->copy()->addDays(17), 'recorded_by' => $recorder->id],
                );
                $this->postSyntheticEntry($expense, 'expense', 'debit', $expense->expense_date, $recorder->id);
            }
        });

        $this->call(EdlTeachCurrentTermDemoSeeder::class);
        $this->seedDashboardActivity($school, $term, $recorder->id);
        $this->command?->info('EDL-TEACH dashboard graphs populated with annual finance, attendance, demographics, events and examination trends; reruns update existing demo records.');
    }

    private function seedDashboardActivity(School $school, $term, int $userId): void
    {
        $students = $school->students()->where('status', 'active')->whereNotNull('school_class_id')->orderBy('id')->get();
        foreach ($students as $studentIndex => $student) {
            if (! $student->gender) {
                $student->update(['gender' => $studentIndex % 2 === 0 ? 'male' : 'female']);
            }
            foreach (range(0, 4) as $dayOffset) {
                $attendanceDate = today()->subDays($dayOffset);
                $attendance = AttendanceRecord::where('student_id', $student->id)->where('session_key', 'annual-dashboard')
                    ->whereDate('attendance_date', $attendanceDate)->first() ?? new AttendanceRecord();
                $attendance->fill(['school_id' => $school->id, 'student_id' => $student->id, 'attendance_date' => $attendanceDate,
                    'session_key' => 'annual-dashboard', 'term_id' => $term->id, 'school_class_id' => $student->school_class_id,
                    'stream_id' => $student->stream_id,
                    'status' => ($studentIndex + $dayOffset) % 9 === 0 ? 'absent' : (($studentIndex + $dayOffset) % 6 === 0 ? 'late' : 'present')])->save();
            }
        }

        foreach ($students->groupBy('school_class_id') as $classId => $classStudents) {
            foreach ([['Beginning of Term Assessment', 0], ['Mid Term Assessment', 7], ['End of Term Assessment', 13]] as $examIndex => [$name, $boost]) {
                $exam = Exam::updateOrCreate(
                    ['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $classId, 'stream_id' => null, 'name' => $name],
                    ['status' => 'published', 'published_at' => now()->subMonths(2 - $examIndex)],
                );
                foreach (['Mathematics', 'English', 'Science'] as $subjectIndex => $subjectName) {
                    $subject = Subject::firstOrCreate(['school_id' => $school->id, 'name' => $subjectName], ['code' => strtoupper(substr($subjectName, 0, 3))]);
                    $paper = ExamPaper::updateOrCreate(['exam_id' => $exam->id, 'subject_id' => $subject->id], ['maximum_score' => 100, 'weighting' => 1]);
                    DB::table('exam_paper_submissions')->updateOrInsert(
                        ['exam_paper_id' => $paper->id],
                        ['status' => 'approved', 'submitted_by' => $userId, 'submitted_at' => now()->subDay(), 'approved_by' => $userId,
                            'approved_at' => now(), 'created_at' => now(), 'updated_at' => now()],
                    );
                    foreach ($classStudents->values() as $studentIndex => $student) {
                        DB::table('exam_marks')->updateOrInsert(
                            ['exam_paper_id' => $paper->id, 'student_id' => $student->id],
                            ['score' => min(98, 55 + $boost + (($studentIndex * 5 + $subjectIndex * 4) % 22)), 'entered_by' => $userId,
                                'created_at' => now(), 'updated_at' => now()],
                        );
                    }
                }
            }
        }

        foreach ([['Demo Academic Review Day', 4, 'academic'], ['Demo Sports and Clubs Day', 11, 'activity'], ['Demo Parent Meeting', 18, 'meeting']] as [$title, $days, $type]) {
            DB::table('school_events')->updateOrInsert(
                ['school_id' => $school->id, 'title' => $title],
                ['term_id' => $term->id, 'event_date' => today()->addDays($days)->toDateString(), 'type' => $type,
                    'target_audience' => 'all', 'description' => 'Synthetic dashboard calendar event.', 'created_at' => now(), 'updated_at' => now()],
            );
        }
    }

    private function postSyntheticEntry(FeePayment|Expense $source, string $type, string $direction, $date, int $userId): void
    {
        $entry = FinanceLedgerEntry::updateOrCreate(
            ['source_type' => $source::class, 'source_id' => $source->id],
            ['school_id' => $source->school_id, 'term_id' => $source->term_id, 'reference' => 'ANNUAL-DEMO-'.$type.'-'.$source->id,
                'entry_type' => $type, 'direction' => $direction, 'amount' => $source->amount,
                'description' => 'Synthetic annual dashboard '.$type, 'status' => 'posted', 'recorded_by' => $userId,
                'approved_by' => $userId, 'approved_at' => $date, 'posted_at' => $date],
        );

        CashPoolEntry::updateOrCreate(
            ['finance_ledger_entry_id' => $entry->id],
            ['school_id' => $source->school_id, 'term_id' => $source->term_id,
                'fee_payment_id' => $source instanceof FeePayment ? $source->id : null,
                'expense_id' => $source instanceof Expense ? $source->id : null,
                'direction' => $direction, 'amount' => $source->amount, 'description' => $entry->description,
                'transacted_at' => $date, 'recorded_by' => $userId],
        );
    }
}
