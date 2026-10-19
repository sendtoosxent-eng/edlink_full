<?php

use App\Models\FinanceLedgerEntry;
use App\Models\PayrollRun;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('shows unpaid monthly salaries to payroll staff and writes idempotent reminders', function () {
    $school = School::create(['name' => 'Payroll Reminder School', 'slug' => 'payroll-reminder-school']);
    SchoolSetting::create(['school_id' => $school->id, 'key' => 'payroll_period', 'value' => 'Monthly']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $bursar = User::factory()->create(['school_id' => $school->id, 'role' => 'bursar']);
    $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher', 'base_salary' => 1200000, 'employment_status' => 'active']);

    $run = PayrollRun::create([
        'school_id' => $school->id,
        'user_id' => $teacher->id,
        'period' => now()->format('Y-m'),
        'payment_type' => 'salary',
        'salary_snapshot' => 1200000,
        'amount' => 500000,
        'method' => 'cash',
        'paid_at' => now(),
        'recorded_by' => $admin->id,
    ]);
    FinanceLedgerEntry::where('source_type', PayrollRun::class)->where('source_id', $run->id)->update(['status' => 'posted']);

    Artisan::call('edlink:payroll-reminders', ['--date' => now()->toDateString()]);

    expect(DB::table('school_notifications')->where('school_id', $school->id)->where('category', 'payroll')->count())->toBe(2)
        ->and(DB::table('school_notifications')->where('user_id', $admin->id)->where('event_key', 'payroll-unpaid:'.now()->format('Y-m'))->value('message'))->toContain('UGX 700,000')
        ->and(DB::table('school_notifications')->where('user_id', $bursar->id)->where('event_key', 'payroll-unpaid:'.now()->format('Y-m'))->exists())->toBeTrue()
        ->and(DB::table('school_notifications')->where('user_id', $teacher->id)->exists())->toBeFalse();

    Artisan::call('edlink:payroll-reminders', ['--date' => now()->toDateString()]);
    expect(DB::table('school_notifications')->where('school_id', $school->id)->where('category', 'payroll')->count())->toBe(2);
});
