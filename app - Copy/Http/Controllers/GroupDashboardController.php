<?php

namespace App\Http\Controllers;

use App\Models\{AttendanceRecord, Expense, FeePayment, Student, User};
use Illuminate\Http\Request;
use Illuminate\View\View;

class GroupDashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        abort_unless($user->canViewGroupDashboard(), 403);

        $groupId = $user->school->school_group_id;
        $schools = $user->schoolAccesses()
            ->where('school_group_id', $groupId)
            ->orderBy('name')
            ->get();
        $schoolIds = $schools->pluck('id');

        $studentCounts = Student::whereIn('school_id', $schoolIds)
            ->where('status', 'active')
            ->selectRaw('school_id, count(*) total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');
        $staffCounts = User::whereIn('school_id', $schoolIds)
            ->whereNotIn('role', ['student', 'parent'])
            ->selectRaw('school_id, count(*) total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');
        $payments = FeePayment::whereIn('school_id', $schoolIds)
            ->selectRaw('school_id, sum(amount) total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');
        $expenses = Expense::whereIn('school_id', $schoolIds)
            ->selectRaw('school_id, sum(amount) total')
            ->groupBy('school_id')
            ->pluck('total', 'school_id');
        $attendance = AttendanceRecord::whereIn('school_id', $schoolIds)
            ->whereDate('attendance_date', today())
            ->selectRaw("school_id, count(*) total, sum(case when status in ('present','late') then 1 else 0 end) present")
            ->groupBy('school_id')
            ->get()
            ->keyBy('school_id');

        $months = collect(range(5, 0))->map(fn (int $offset) => now()->startOfMonth()->subMonths($offset));
        $monthStart = $months->first()->copy()->startOfMonth();
        $monthEnd = $months->last()->copy()->endOfMonth();
        $monthlyPayments = FeePayment::whereIn('school_id', $schoolIds)
            ->whereBetween('paid_at', [$monthStart, $monthEnd])
            ->get(['amount', 'paid_at'])
            ->groupBy(fn (FeePayment $payment) => $payment->paid_at->format('Y-m'))
            ->map->sum('amount');
        $monthlyExpenses = Expense::whereIn('school_id', $schoolIds)
            ->whereBetween('expense_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
            ->get(['amount', 'expense_date'])
            ->groupBy(fn (Expense $expense) => $expense->expense_date->format('Y-m'))
            ->map->sum('amount');

        $monthlyLabels = $months->map->format('M Y')->values();
        $monthlyFeeSeries = $months->map(fn ($month) => (float) ($monthlyPayments[$month->format('Y-m')] ?? 0))->values();
        $monthlyExpenseSeries = $months->map(fn ($month) => (float) ($monthlyExpenses[$month->format('Y-m')] ?? 0))->values();
        $branchLabels = $schools->map(fn ($school) => $school->branch_name ?: $school->name)->values();
        $branchAttendanceRates = $schools->map(function ($school) use ($attendance) {
            $daily = $attendance->get($school->id);
            return $daily && $daily->total ? round(($daily->present / $daily->total) * 100, 1) : 0;
        })->values();
        $genderCounts = Student::whereIn('school_id', $schoolIds)
            ->where('status', 'active')
            ->selectRaw('lower(gender) gender_key, count(*) total')
            ->groupBy('gender_key')
            ->pluck('total', 'gender_key');
        $genderSeries = [
            (int) ($genderCounts['male'] ?? $genderCounts['m'] ?? 0),
            (int) ($genderCounts['female'] ?? $genderCounts['f'] ?? 0),
            (int) $genderCounts->except(['male', 'm', 'female', 'f'])->sum(),
        ];

        return view('group-dashboard', compact(
            'schools', 'studentCounts', 'staffCounts', 'payments', 'expenses', 'attendance',
            'monthlyLabels', 'monthlyFeeSeries', 'monthlyExpenseSeries', 'branchLabels',
            'branchAttendanceRates', 'genderSeries'
        ));
    }
}
