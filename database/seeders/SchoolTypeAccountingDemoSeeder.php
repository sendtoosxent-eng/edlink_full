<?php

namespace Database\Seeders;

use App\Models\AccountingJournal;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\StudentFeeAssessment;
use App\Models\User;
use App\Services\AccountingSetupService;
use App\Services\DoubleEntryService;
use App\Services\FinanceLedgerService;
use Illuminate\Database\Seeder;

class SchoolTypeAccountingDemoSeeder extends Seeder
{
    public function run(): void
    {
        School::query()
            ->where('is_demo', true)
            ->whereIn('school_type', ['kindergarten', 'primary', 'secondary', 'tertiary'])
            ->orderBy('id')
            ->get()
            ->groupBy('school_type')
            ->each(fn ($schools) => $this->seedSchool($schools->first()));

        $this->command?->info('Posted fee and expense accounting demos for each public school type.');
    }

    private function seedSchool(School $school): void
    {
        $term = $school->currentTerm();
        $student = $school->students()->where('status', 'active')->orderBy('id')->first();
        $maker = User::where('school_id', $school->id)->where('role', 'bursar')->first()
            ?? User::where('school_id', $school->id)->whereIn('role', ['admin', 'teacher'])->first();
        $checker = User::where('school_id', $school->id)->where('role', 'admin')->whereKeyNot($maker?->id)->first()
            ?? User::where('school_id', $school->id)->whereKeyNot($maker?->id)->first();

        if (! $term || ! $student || ! $maker || ! $checker) {
            $this->command?->warn("Skipped accounting demo for {$school->name}: term, student, maker, or checker is missing.");

            return;
        }

        app(AccountingSetupService::class)->activate($school, $maker->id);
        $currency = strtoupper((string) SchoolSetting::getValue($school->id, 'accounting_currency', 'UGX'));
        $receivable = LedgerAccount::where('school_id', $school->id)->where('code', '1210')->firstOrFail();
        $feeIncome = LedgerAccount::where('school_id', $school->id)->where('code', '4100')->firstOrFail();
        $bank = FinancialAccount::where('school_id', $school->id)->where('type', 'bank')->firstOrFail();
        $utilities = LedgerAccount::where('school_id', $school->id)->where('code', '5400')->firstOrFail();
        $supplies = LedgerAccount::where('school_id', $school->id)->where('code', '5200')->firstOrFail();
        $type = $this->publicType($school);
        $assessmentAmount = $this->assessmentAmount($type);

        $assessment = StudentFeeAssessment::firstOrCreate(
            ['school_id' => $school->id, 'idempotency_key' => "demo-accounting:{$type}:assessment"],
            ['student_id' => $student->id, 'term_id' => $term->id, 'fee_item_code' => 'TUITION', 'description' => 'Demo tuition assessment', 'amount' => $assessmentAmount, 'status' => 'submitted', 'created_by' => $maker->id],
        );

        $assessmentJournal = AccountingJournal::where('school_id', $school->id)
            ->where('idempotency_key', "demo-accounting:{$type}:assessment-journal")
            ->first();
        if (! $assessmentJournal) {
            $assessmentJournal = app(DoubleEntryService::class)->create([
                'school_id' => $school->id, 'term_id' => $term->id, 'journal_date' => now()->toDateString(),
                'reference' => 'DEMO-ASSESS-'.strtoupper($type), 'description' => 'Demo tuition assessment',
                'journal_type' => 'fee_assessment', 'currency' => $currency,
                'source_type' => StudentFeeAssessment::class, 'source_id' => $assessment->id,
                'idempotency_key' => "demo-accounting:{$type}:assessment-journal",
            ], [
                ['ledger_account_id' => $receivable->id, 'term_id' => $term->id, 'student_id' => $student->id, 'description' => 'Tuition charged', 'debit' => $assessmentAmount, 'credit' => 0],
                ['ledger_account_id' => $feeIncome->id, 'term_id' => $term->id, 'student_id' => $student->id, 'description' => 'Tuition income', 'debit' => 0, 'credit' => $assessmentAmount],
            ], $maker->id);
            app(DoubleEntryService::class)->submit($assessmentJournal, $maker->id);
            app(DoubleEntryService::class)->approve($assessmentJournal->fresh(), $checker->id);
            app(DoubleEntryService::class)->post($assessmentJournal->fresh(), $checker->id);
            $assessment->update(['journal_id' => $assessmentJournal->id, 'status' => 'posted', 'posted_at' => now()]);
        }

        $payment = FeePayment::firstOrCreate(
            ['school_id' => $school->id, 'transaction_id' => 'DEMO-FEE-'.strtoupper($type).'-001'],
            ['financial_account_id' => $bank->id, 'student_id' => $student->id, 'term_id' => $term->id, 'amount' => $assessmentAmount * 0.6, 'method' => 'bank', 'bank_slip_number' => 'DEMO-BANK-'.strtoupper($type), 'notes' => 'Posted accounting demonstration payment', 'recorded_by' => $maker->id, 'paid_at' => now()],
        );
        $this->approvePending($payment, $checker);

        $immediate = Expense::firstOrCreate(
            ['school_id' => $school->id, 'reference_number' => 'DEMO-EXP-'.strtoupper($type).'-CASH'],
            ['financial_account_id' => $bank->id, 'expense_ledger_account_id' => $utilities->id, 'term_id' => $term->id, 'settlement_type' => 'immediate', 'category' => 'Utilities', 'payee' => 'Demo Utility Provider', 'amount' => $this->expenseAmount($type), 'description' => 'Electricity and water demonstration expense', 'expense_date' => now(), 'recorded_by' => $maker->id],
        );
        $this->approvePending($immediate, $checker);

        $credit = Expense::firstOrCreate(
            ['school_id' => $school->id, 'reference_number' => 'DEMO-EXP-'.strtoupper($type).'-CREDIT'],
            ['expense_ledger_account_id' => $supplies->id, 'term_id' => $term->id, 'settlement_type' => 'credit', 'category' => 'Supplies', 'payee' => 'Demo Learning Supplies Ltd', 'amount' => $this->expenseAmount($type) * 0.5, 'description' => 'Learning materials bought on supplier credit', 'expense_date' => now(), 'recorded_by' => $maker->id],
        );
        $this->approvePending($credit, $checker);
    }

    private function approvePending(FeePayment|Expense $source, User $checker): void
    {
        $entry = FinanceLedgerEntry::where('source_type', $source::class)->where('source_id', $source->id)->firstOrFail();
        if ($entry->status === 'pending') {
            app(FinanceLedgerService::class)->approve($entry, $checker->id);
        }
    }

    private function publicType(School $school): string
    {
        return $school->school_type === 'tertiary' ? 'vocational' : $school->school_type;
    }

    private function assessmentAmount(string $type): int
    {
        return ['kindergarten' => 480000, 'primary' => 525000, 'secondary' => 850000, 'vocational' => 720000][$type];
    }

    private function expenseAmount(string $type): int
    {
        return ['kindergarten' => 90000, 'primary' => 120000, 'secondary' => 180000, 'vocational' => 210000][$type];
    }
}
