<?php

namespace App\Services;

use App\Models\PayrollRun;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PayrollBalanceService
{
    public function summary(User $staff, string $period): array
    {
        $end = Carbon::createFromFormat('!Y-m', $period)->min(now()->startOfMonth());
        $start = Carbon::parse($staff->joined_at ?? $staff->created_at ?? now())->startOfMonth();
        $runs = PayrollRun::posted()->where('school_id', $staff->school_id)
            ->where('user_id', $staff->id)->where('period', '<=', $period)->get();
        $existing = DB::table('staff_salary_balances')->where('school_id', $staff->school_id)
            ->where('user_id', $staff->id)->pluck('salary', 'period');

        // Save each month's salary once, so later salary changes cannot rewrite arrears.
        if (($staff->employment_status ?? 'active') === 'active' && (float) $staff->base_salary > 0 && ! in_array($staff->role, ['student', 'parent'], true)) {
            for ($month = $start->copy(); $month->lte($end); $month->addMonth()) {
                $key = $month->format('Y-m');
                if ($existing->has($key)) {
                    continue;
                }
                $snapshot = $runs->where('period', $key)->first(fn ($run) => (float) $run->salary_snapshot > 0)?->salary_snapshot;
                DB::table('staff_salary_balances')->insertOrIgnore([
                    'school_id' => $staff->school_id, 'user_id' => $staff->id, 'period' => $key,
                    'salary' => $snapshot ?? $staff->base_salary ?? 0, 'created_at' => now(), 'updated_at' => now(),
                ]);
            }
        }

        $balances = DB::table('staff_salary_balances')->where('school_id', $staff->school_id)
            ->where('user_id', $staff->id)->where('period', '<=', $period)->orderBy('period')->get();
        $outstanding = $balances->mapWithKeys(fn ($balance) => [
            $balance->period => max(0, (float) $balance->salary - (float) $runs->where('period', $balance->period)->sum('amount')),
        ]);
        $currentRuns = $runs->where('period', $period);
        $remaining = (float) ($outstanding[$period] ?? 0);
        $received = (float) $currentRuns->sum('amount');

        return [
            'salary' => (float) ($balances->firstWhere('period', $period)?->salary ?? 0),
            'advances' => (float) $currentRuns->where('payment_type', 'advance')->sum('amount'),
            'salary_paid' => (float) $currentRuns->where('payment_type', 'salary')->sum('amount'),
            'received' => $received, 'remaining' => $remaining, 'salary_outstanding' => $remaining,
            'salary_status' => $remaining <= 0 ? 'paid' : ($received > 0 ? 'partial' : 'unpaid'),
            'arrears' => (float) $outstanding->except($period)->sum(),
            'total_outstanding' => (float) $outstanding->sum(),
            'unpaid_periods' => $outstanding->filter(fn ($amount) => $amount > 0)->all(),
        ];
    }
}
