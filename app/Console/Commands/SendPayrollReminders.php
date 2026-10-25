<?php

namespace App\Console\Commands;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Services\PayrollBalanceService;
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
                    ->get()
                    ->map(function ($staff) use ($period) {
                        $summary = app(PayrollBalanceService::class)->summary($staff, $period);
                        return ['staff' => $staff, 'outstanding' => $summary['total_outstanding'], 'arrears' => $summary['arrears']];
                    })
                    ->filter(fn ($item) => $item['outstanding'] > 0)
                    ->values();

                if ($unpaid->isEmpty()) {
                    continue;
                }

                $currency = (string) SchoolSetting::getValue($school->id, 'currency', 'UGX');
                $names = $unpaid->take(5)->map(fn ($item) => $item['staff']->name.' ('.$currency.' '.number_format($item['outstanding']).')')->join(', ');
                if ($unpaid->count() > 5) {
                    $names .= ' and '.($unpaid->count() - 5).' more';
                }
                $total = $unpaid->sum('outstanding');
                $message = 'Unpaid salary balances through '.$period.' total '.$currency.' '.number_format($total)
                    .' (including '.$currency.' '.number_format($unpaid->sum('arrears')).' carried forward): '.$names
                    .'. Open Staff Payroll and choose an unpaid month to record its payment.';
                $recipients = $school->users()->whereIn('role', ['admin', 'superadmin', 'bursar'])->pluck('id');
                foreach ($recipients as $recipientId) {
                    $key = ['school_id' => $school->id, 'user_id' => $recipientId, 'event_key' => 'payroll-unpaid:'.$period];
                    $existing = DB::table('school_notifications')->where($key)->first();
                    if (! $existing || $existing->message !== $message) {
                        DB::table('school_notifications')->updateOrInsert($key, [
                            'title' => 'Monthly payroll reminder', 'message' => $message, 'type' => 'warning',
                            'category' => 'payroll', 'read_at' => null, 'created_at' => $existing?->created_at ?? now(), 'updated_at' => now(),
                        ]);
                    }
                    $notified++;
                }
            }
        });

        $this->info("Payroll reminders written: {$notified}");

        return self::SUCCESS;
    }
}
