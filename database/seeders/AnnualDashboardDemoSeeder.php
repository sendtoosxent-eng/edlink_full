<?php

namespace Database\Seeders;

use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\School;
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
        $this->command?->info('EDL-TEACH annual dashboard data seeded from January through '.now()->format('F').'; reruns update existing demo records.');
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
