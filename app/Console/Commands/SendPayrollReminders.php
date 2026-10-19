<?php

namespace App\Console\Commands;

use App\Models\PayrollRun;
use App\Models\School;
use App\Models\SchoolSetting;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendPayrollReminders extends Command
{
    protected $signature = 'edlink:payroll-reminders {--date= : Date to evaluate, in Y-m-d format}';

    protected $description = 'Notify bursars and administrators about unpaid monthly salaries';

    public function handle(): int
    {
        $date = $this->option('date') ? Carbon::parse($this->option('date')) : now();
        $period = $date->format('Y-m');
        $notified = 0;

        School::query()->chunkById(100, function ($schools) use ($period, &$notified): void {
            foreach ($schools as $school) {
                $setting = strtolower(trim((string) SchoolSetting::getValue($school->id, 'payroll_period', 'Monthly')));
                if ($setting !== 'monthly') {
                    continue;
                }

                $unpaid = $school->users()
                    ->where('employment_status', 'active')
                    ->whereNotIn('role', ['student', 'parent'])
                    ->where('base_salary', '>', 0)
                    ->get()
                    ->map(function ($staff) use ($period) {
                        $paid = (float) PayrollRun::posted()->where('school_id', $staff->school_id)
                            ->where('user_id', $staff->id)->where('period', $period)->where('payment_type', 'salary')->sum('amount');
                        return ['staff' => $staff, 'paid' => $paid, 'outstanding' => max(0, (float) $staff->base_salary - $paid)];
                    })
                    ->filter(fn ($item) => $item['outstanding'] > 0)
                    ->values();

                if ($unpaid->isEmpty()) {
                    continue;
                }

                $names = $unpaid->take(5)->map(fn ($item) => $item['staff']->name.' (UGX '.number_format($item['outstanding']).')')->join(', ');
                if ($unpaid->count() > 5) {
                    $names .= ' and '.($unpaid->count() - 5).' more';
                }
                $total = $unpaid->sum('outstanding');
                $recipients = $school->users()->whereIn('role', ['admin', 'superadmin', 'bursar'])->pluck('id');
                foreach ($recipients as $recipientId) {
                    DB::table('school_notifications')->updateOrInsert(
                        ['school_id' => $school->id, 'user_id' => $recipientId, 'event_key' => 'payroll-unpaid:'.$period],
                        ['title' => 'Monthly payroll reminder', 'message' => 'The following staff have unpaid '.$period.' salary balances totalling UGX '.number_format($total).': '.$names.'.', 'type' => 'warning', 'category' => 'payroll', 'read_at' => null, 'created_at' => now(), 'updated_at' => now()],
                    );
                    $notified++;
                }
            }
        });

        $this->info("Payroll reminders written: {$notified}");

        return self::SUCCESS;
    }
}
