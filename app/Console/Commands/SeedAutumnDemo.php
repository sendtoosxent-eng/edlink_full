<?php

namespace App\Console\Commands;

use App\Models\AttendanceRecord;
use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinancialAccount;
use App\Models\School;
use App\Models\User;
use App\Services\AccountingPostingService;
use App\Support\DemoAccounts;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedAutumnDemo extends Command
{
    protected $signature = 'edlink:seed-autumn-demo {--school=* : Public demo school number; defaults to all public demos} {--year=2026 : Demo calendar year}';

    protected $description = 'Add synthetic demo attendance and posted finance trends for October 20 through November 30';

    public function handle(): int
    {
        $year = (string) $this->option('year');
        if (! preg_match('/\A20\d{2}\z/', $year)) {
            $this->error('Use a four-digit demo year between 2000 and 2099.');

            return self::FAILURE;
        }
        $allowed = collect(DemoAccounts::schoolTypes())->pluck('school_number')->all();
        $numbers = $this->option('school') ?: $allowed;
        $schools = School::whereIn('school_number', $numbers)->where('is_demo', true)->get();
        if (array_diff($numbers, $allowed) || $schools->count() !== count(array_unique($numbers))) {
            $this->error('Every selected school must exist, be a public demo, and have is_demo enabled. No data was added.');

            return self::FAILURE;
        }
        $start = CarbonImmutable::create((int) $year, 10, 20)->startOfDay();
        $end = CarbonImmutable::create((int) $year, 11, 30)->startOfDay();
        // Validate all target schools before writing to any of them.
        foreach ($schools as $school) {
            $term = $school->currentTerm();
            if (! $term || ! $term->isOpen() || $term->isLocked() || (int) $term->year !== (int) $year
                || ! $school->students()->where('status', 'active')->whereNotNull('school_class_id')->exists()
                || ! User::where('school_id', $school->id)->where('role', 'bursar')->exists()
                || ! User::where('school_id', $school->id)->where('role', 'admin')->exists()
                || ! FinancialAccount::where('school_id', $school->id)->where('is_active', true)->whereNotNull('ledger_account_id')->exists()) {
                $this->error($school->school_number.' requires an open current term in the demo year, active learners, admin/bursar users and a mapped financial account. No data was added.');

                return self::FAILURE;
            }
        }
        DB::transaction(function () use ($schools, $start, $end): void {
            foreach ($schools as $school) {
                $term = $school->currentTerm();
                $students = $school->students()->where('status', 'active')->whereNotNull('school_class_id')->orderBy('id')->get();
                $recorder = User::where('school_id', $school->id)->where('role', 'bursar')->firstOrFail();
                $approver = User::where('school_id', $school->id)->where('role', 'admin')->firstOrFail();
                $accounts = FinancialAccount::where('school_id', $school->id)->where('is_active', true)->whereNotNull('ledger_account_id')->orderBy('id')->get();
                $existing = AttendanceRecord::where('school_id', $school->id)->where('session_key', 'daily')
                    ->whereBetween('attendance_date', [$start->toDateString(), $end->endOfDay()->toDateTimeString()])
                    ->get(['student_id', 'attendance_date'])->mapWithKeys(fn ($r) => [$r->student_id.':'.$r->attendance_date->toDateString() => true])->all();
                $attendance = [];
                $payments = 0;
                $expenses = 0;
                $timestamp = now()->toDateTimeString();
                for ($date = $start, $offset = 0; $date->lte($end); $date = $date->addDay(), $offset++) {
                    if ($date->isWeekend()) {
                        continue;
                    }
                    foreach ($students as $index => $student) {
                        if (isset($existing[$student->id.':'.$date->toDateString()])) {
                            continue;
                        }
                        $roll = ($index * 7 + $offset * 13) % 100;
                        $attendance[] = ['school_id' => $school->id, 'term_id' => $term->id, 'student_id' => $student->id,
                            'school_class_id' => $student->school_class_id, 'stream_id' => $student->stream_id,
                            'attendance_date' => $date->toDateTimeString(), 'session_key' => 'daily',
                            'status' => $roll < 5 + ($offset % 4) ? 'absent' : ($roll < 12 ? 'late' : 'present'),
                            'recorded_by' => $recorder->id, 'note' => 'Synthetic Oct–Nov demo attendance; not an actual register.',
                            'created_at' => $timestamp, 'updated_at' => $timestamp];
                    }
                    for ($slot = 0; $slot < 3 + ($offset % 3); $slot++) {
                        $student = $students[($offset * 7 + $slot * 11) % $students->count()];
                        $method = ['cash', 'mobile_money', 'bank'][($slot + $offset) % 3];
                        $account = $accounts->firstWhere('type', $method) ?? $accounts->first();
                        $reference = 'AUTUMN-DEMO-PAY-'.$date->format('Ymd').'-'.$slot;
                        $payment = FeePayment::firstOrCreate(['school_id' => $school->id, 'transaction_id' => $reference], [
                            'student_id' => $student->id, 'term_id' => $term->id, 'financial_account_id' => $account->id,
                            'amount' => 25000 + (($offset * 9 + $slot * 7) % 18) * 5000,
                            'method' => $method,
                            'notes' => 'Synthetic Oct–Nov demo fee income; no money received.', 'recorded_by' => $recorder->id,
                            'paid_at' => $date->setTime(9 + $slot, 15),
                        ]);
                        $this->post($payment, $approver->id);
                        $payments++;
                    }
                    if (in_array($date->dayOfWeekIso, [1, 3, 5], true)) {
                        $category = ['Utilities', 'Supplies', 'Maintenance', 'Transport', 'Meals', 'Other'][$offset % 6];
                        $expense = Expense::firstOrCreate(['school_id' => $school->id, 'reference_number' => 'AUTUMN-DEMO-EXP-'.$date->format('Ymd')], [
                            'term_id' => $term->id, 'financial_account_id' => $accounts[$offset % $accounts->count()]->id,
                            'settlement_type' => 'immediate', 'category' => $category, 'payee' => 'Demo '.$category.' Supplier',
                            'amount' => 30000 + (($offset * 11) % 20) * 7500,
                            'description' => 'Synthetic Oct–Nov demo operating expense; no actual purchase.',
                            'expense_date' => $date, 'recorded_by' => $recorder->id,
                        ]);
                        $this->post($expense, $approver->id);
                        $expenses++;
                    }
                }
                foreach (array_chunk($attendance, 200) as $chunk) {
                    DB::table('attendance_records')->insert($chunk);
                }
                $this->info($school->school_number.': '.count($attendance).' attendance rows added; '.$payments.' fee payments and '.$expenses.' expenses available.');
            }
        });
        $this->info('Demo range: '.$start->toDateString().' to '.$end->toDateString().'. Future November dates are intentional demo data. No receipt emails or app notifications sent.');

        return self::SUCCESS;
    }

    private function post(FeePayment|Expense $source, int $approverId): void
    {
        $entry = $source->ledgerEntry()->firstOrFail();
        if ($entry->status === 'posted') {
            return;
        }
        if ($entry->status !== 'pending') {
            throw new \RuntimeException('An existing demo transaction was rejected or reversed; refusing to change it.');
        }
        // Use the real balanced accounting postings, without approval's outbound receipt hooks.
        app(AccountingPostingService::class)->postApprovedLedgerEntry($entry, $approverId);
        $date = $source instanceof FeePayment ? $source->paid_at : $source->expense_date;
        $entry->update(['status' => 'posted', 'approved_by' => $approverId, 'approved_at' => $date, 'posted_at' => $date]);
        CashPoolEntry::firstOrCreate(['finance_ledger_entry_id' => $entry->id], [
            'school_id' => $source->school_id, 'term_id' => $source->term_id, 'financial_account_id' => $source->financial_account_id,
            'fee_payment_id' => $source instanceof FeePayment ? $source->id : null, 'expense_id' => $source instanceof Expense ? $source->id : null,
            'direction' => $entry->direction, 'amount' => $entry->amount, 'description' => $entry->description,
            'transacted_at' => $date, 'recorded_by' => $source->recorded_by,
        ]);
    }
}
