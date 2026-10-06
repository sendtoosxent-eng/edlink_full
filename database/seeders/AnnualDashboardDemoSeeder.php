<?php

namespace Database\Seeders;

use App\Models\AccountingJournal;
use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\School;
use App\Models\Subject;
use App\Models\User;
use App\Services\FinanceLedgerService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AnnualDashboardDemoSeeder extends Seeder
{
    public function run(): void
    {
        $school = School::where('school_number', 'EDL-TEACH')->where('is_demo', true)->firstOrFail();
        $term = $school->currentTerm();
        $students = $school->students()->where('status', 'active')->whereNotNull('school_class_id')->orderBy('id')->take(4)->get();
        $approver = User::where('school_id', $school->id)->where('role', 'admin')->first();
        $recorder = User::where('school_id', $school->id)->whereIn('role', ['bursar', 'admin'])
            ->when($approver, fn ($query) => $query->whereKeyNot($approver->id))->first();
        if ($approver && ! $recorder) {
            $recorder = User::create([
                'school_id' => $school->id, 'name' => 'Annual Dashboard Demo Recorder',
                'email' => 'annual-dashboard-recorder-'.$school->id.'@edlink.local',
                'password' => Str::random(40), 'role' => 'bursar', 'employment_status' => 'active',
                'email_verified_at' => now(),
            ]);
        }
        $financialAccount = FinancialAccount::where('school_id', $school->id)->where('is_active', true)
            ->whereNotNull('ledger_account_id')->orderByRaw("type = 'bank' desc")->first();

        if (! $term || $students->isEmpty() || ! $recorder || ! $approver || ! $financialAccount) {
            throw new \RuntimeException('EDL-TEACH needs a current term, active learners, separate accounting maker/checker users and a mapped financial account before annual dashboard data can be seeded.');
        }

        DB::transaction(function () use ($school, $term, $students, $recorder, $approver, $financialAccount): void {
            foreach (range(1, now()->month) as $month) {
                $date = now()->setDate((int) $term->year, $month, 1)->startOfDay();
                $monthCode = $date->format('Ym');

                foreach ($students->take(2) as $index => $student) {
                    $payment = FeePayment::updateOrCreate(
                        ['school_id' => $school->id, 'transaction_id' => "ANNUAL-DEMO-PAY-{$monthCode}-{$student->id}"],
                        ['student_id' => $student->id, 'term_id' => $term->id, 'financial_account_id' => $financialAccount->id,
                            'amount' => 120000 + ($month * 15000) + ($index * 35000),
                            'method' => $index ? 'mobile_money' : 'bank', 'notes' => 'Synthetic annual dashboard payment.',
                            'recorded_by' => $recorder->id, 'paid_at' => $date->copy()->addDays(5 + ($index * 8))],
                    );
                    $this->postSyntheticEntry($payment, $payment->paid_at, $recorder->id, $approver->id);
                }

                $expense = Expense::updateOrCreate(
                    ['school_id' => $school->id, 'reference_number' => "ANNUAL-DEMO-EXP-{$monthCode}"],
                    ['term_id' => $term->id, 'financial_account_id' => $financialAccount->id,
                        'settlement_type' => 'immediate', 'category' => ['Utilities', 'Supplies', 'Maintenance', 'Transport'][$month % 4],
                        'payee' => 'Annual Dashboard Demo Supplier', 'amount' => 85000 + ($month * 18000),
                        'description' => 'Synthetic monthly operating expense for dashboard demonstration.',
                        'expense_date' => $date->copy()->addDays(17), 'recorded_by' => $recorder->id],
                );
                $this->postSyntheticEntry($expense, $expense->expense_date, $recorder->id, $approver->id);
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
                    ->whereDate('attendance_date', $attendanceDate)->first() ?? new AttendanceRecord;
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

    private function postSyntheticEntry(FeePayment|Expense $source, $date, int $recorderId, int $approverId): void
    {
        $entry = FinanceLedgerEntry::where('source_type', $source::class)->where('source_id', $source->id)->firstOrFail();
        $journalExists = AccountingJournal::where('school_id', $source->school_id)
            ->where('source_type', $source::class)->where('source_id', $source->id)->exists();

        if (! $journalExists) {
            // Repair records produced by the old dashboard-only seeder before approving normally.
            CashPoolEntry::where('finance_ledger_entry_id', $entry->id)->delete();
            $entry->update([
                'financial_account_id' => $source->financial_account_id, 'status' => 'pending',
                'recorded_by' => $recorderId, 'approved_by' => null, 'approved_at' => null,
            ]);
            $entry = app(FinanceLedgerService::class)->approve($entry, $approverId);
        }

        $entry->update(['approved_at' => $date, 'posted_at' => $date]);
        CashPoolEntry::where('finance_ledger_entry_id', $entry->id)->update([
            'financial_account_id' => $source->financial_account_id, 'transacted_at' => $date,
        ]);
    }
}
