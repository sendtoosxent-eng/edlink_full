<?php

use App\Livewire\Payroll;
use App\Models\PayrollRun;
use App\Models\School;
use App\Models\User;
use App\Services\PayrollBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 25)->startOfDay());
    $this->school = School::create(['name' => 'Salary Rollover School', 'slug' => 'salary-rollover']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin', 'base_salary' => 0]);
    $this->teacher = User::factory()->create([
        'school_id' => $this->school->id, 'role' => 'teacher', 'base_salary' => 1000000,
        'employment_status' => 'active', 'joined_at' => '2026-10-01',
    ]);
    $this->payment = function (string $period, float $amount, string $type = 'salary', string $status = 'posted'): PayrollRun {
        $run = PayrollRun::create([
            'school_id' => $this->school->id, 'user_id' => $this->teacher->id,
            'period' => $period, 'payment_type' => $type, 'salary_snapshot' => 1000000,
            'amount' => $amount, 'method' => 'cash', 'paid_at' => now(), 'recorded_by' => $this->admin->id,
        ]);
        $run->ledgerEntry()->update(['status' => $status]);

        return $run;
    };
});

it('carries partial balances into the new month and clears them only with approved payments', function () {
    ($this->payment)('2026-10', 400000);
    ($this->payment)('2026-10', 100000, 'advance');
    ($this->payment)('2026-10', 200000, 'salary', 'pending');
    $service = app(PayrollBalanceService::class);
    expect($service->summary($this->teacher, '2026-10')['total_outstanding'])->toBe(500000.0);

    $this->travelTo(now()->setDate(2026, 11, 1));
    $summary = $service->summary($this->teacher, '2026-11');
    expect($summary['remaining'])->toBe(1000000.0)
        ->and($summary['arrears'])->toBe(500000.0)
        ->and($summary['total_outstanding'])->toBe(1500000.0);
    ($this->payment)('2026-10', 500000);
    expect($service->summary($this->teacher, '2026-11')['total_outstanding'])->toBe(1000000.0);

    $this->actingAs($this->admin);
    Livewire::test(Payroll::class)->call('selectStaff', $this->teacher->id)
        ->assertSee('Total owed through 2026-11')->assertSee('1,000,000')
        ->set('period', '2026-10')->assertSet('amount', '0');
});

it('does not duplicate months and preserves old salaries when the salary changes', function () {
    $service = app(PayrollBalanceService::class);
    $service->summary($this->teacher, '2026-10');
    $service->summary($this->teacher, '2026-10');
    expect(DB::table('staff_salary_balances')->where('user_id', $this->teacher->id)->count())->toBe(1);
    $this->teacher->update(['base_salary' => 1200000]);
    $this->travelTo(now()->setDate(2026, 12, 1));
    $summary = $service->summary($this->teacher, '2026-12');
    expect($summary['arrears'])->toBe(2200000.0)->and($summary['total_outstanding'])->toBe(3400000.0);
    expect($service->summary($this->teacher, '2026-10')['salary'])->toBe(1000000.0);
});

it('notifies admins at rollover with arrears and preserves read reminders on unchanged reruns', function () {
    ($this->payment)('2026-10', 250000);
    $this->travelTo(now()->setDate(2026, 11, 1));
    Artisan::call('edlink:payroll-reminders');
    $query = DB::table('school_notifications')->where('user_id', $this->admin->id)->where('event_key', 'payroll-unpaid:2026-11');
    expect($query->value('message'))->toContain('UGX 1,750,000')->toContain('UGX 750,000 carried forward');
    $query->update(['read_at' => now()]);
    Artisan::call('edlink:payroll-reminders');
    expect($query->count())->toBe(1)->and($query->value('read_at'))->not->toBeNull();
    expect(DB::table('school_notifications')->where('user_id', $this->teacher->id)->exists())->toBeFalse();
});

it('starts at the joining month and does not accrue future months', function () {
    $this->teacher->update(['joined_at' => '2026-11-01']);
    expect(app(PayrollBalanceService::class)->summary($this->teacher, '2026-12')['total_outstanding'])->toBe(0.0);
    $this->teacher->update(['joined_at' => '2026-10-01']);
    ($this->payment)('2026-10', 1000000);
    Artisan::call('edlink:payroll-reminders');
    expect(DB::table('school_notifications')->where('school_id', $this->school->id)->count())->toBe(0);
    expect(app(PayrollBalanceService::class)->summary($this->teacher, '2026-12')['total_outstanding'])->toBe(0.0);
});

it('still reminds admins about arrears when the current month is fully paid', function () {
    ($this->payment)('2026-10', 300000);
    $this->travelTo(now()->setDate(2026, 11, 1));
    ($this->payment)('2026-11', 1000000);
    Artisan::call('edlink:payroll-reminders');
    $message = DB::table('school_notifications')->where('user_id', $this->admin->id)
        ->where('event_key', 'payroll-unpaid:2026-11')->value('message');
    expect($message)->toContain('total UGX 700,000')->toContain('UGX 700,000 carried forward');
});

it('catches up missed months across a year boundary and scopes posted payments by school', function () {
    $other = School::create(['name' => 'Other Payroll School', 'slug' => 'other-payroll']);
    $run = ($this->payment)('2026-10', 1000000);
    $run->update(['school_id' => $other->id]);
    $this->travelTo(now()->setDate(2027, 1, 1));
    $summary = app(PayrollBalanceService::class)->summary($this->teacher, '2027-01');
    expect($summary['arrears'])->toBe(3000000.0)->and($summary['total_outstanding'])->toBe(4000000.0);
});
