<?php

use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use App\Models\DemoRegistration;
use App\Models\School;
use App\Models\User;
use App\Notifications\QueuedResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function platformSchoolAdmin(): PlatformAdmin
{
    return PlatformAdmin::create([
        'name' => 'Platform Owner',
        'email' => 'owner@edlink.test',
        'password' => 'password',
        'role' => 'platform_owner',
        'is_active' => true,
    ]);
}

it('shows a platform school profile', function () {
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'Demo Academy', 'slug' => 'demo-academy', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'trial', 'is_demo' => true]);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->get(route('platform.schools.show', $school))
        ->assertOk()
        ->assertSee('Demo Academy')
        ->assertSee($school->school_number)
        ->assertSee('School information');
});

it('sends and audits a school scoped password reset from the platform', function () {
    Notification::fake();
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'Onboarding Demo', 'slug' => 'onboarding-demo', 'license_status' => 'trial', 'is_demo' => true]);
    $user = User::factory()->create(['school_id' => $school->id, 'email' => 'admin@onboarding.test']);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->post(route('platform.schools.users.password-reset', [$school, $user]))
        ->assertRedirect()->assertSessionHas('status');

    Notification::assertSentTo($user, QueuedResetPassword::class);
    expect(PlatformAuditLog::where('event', 'platform.school_user.password_reset_requested')->where('metadata->school_id', $school->id)->exists())->toBeTrue();
});

it('does not send a platform password reset across schools', function () {
    Notification::fake();
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'First Demo', 'slug' => 'first-demo', 'license_status' => 'trial', 'is_demo' => true]);
    $other = School::create(['name' => 'Other Demo', 'slug' => 'other-demo', 'license_status' => 'trial', 'is_demo' => true]);
    $otherUser = User::factory()->create(['school_id' => $other->id]);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->post(route('platform.schools.users.password-reset', [$school, $otherUser]))
        ->assertNotFound();

    Notification::assertNothingSent();
});

it('permanently removes a demo school and audits the action', function () {
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'Temporary Demo', 'slug' => 'temporary-demo', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'trial', 'is_demo' => true]);
    $user = User::factory()->create(['school_id' => $school->id]);
    DemoRegistration::create(['email' => $user->email, 'used_at' => now()]);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->delete(route('platform.schools.destroy', $school), ['school_number' => $school->school_number])
        ->assertRedirect(route('platform.schools'));

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);
    $this->assertDatabaseMissing('users', ['school_id' => $school->id]);
    $this->assertDatabaseMissing('demo_registrations', ['email' => $user->email]);
    expect(PlatformAuditLog::where('event', 'platform.school.deleted')->exists())->toBeTrue();
});

it('permanently removes a demo school with populated accounting dependencies', function () {
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'Finance Demo', 'slug' => 'finance-demo', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'trial', 'is_demo' => true]);
    $termId = DB::table('terms')->insertGetId(['school_id' => $school->id, 'name' => 'Term 1', 'year' => now()->year, 'term_number' => 1, 'is_current' => true, 'status' => 'open', 'created_at' => now(), 'updated_at' => now()]);
    $supplierId = DB::table('accounting_suppliers')->insertGetId(['school_id' => $school->id, 'name' => 'Demo Supplier', 'is_active' => true, 'created_at' => now(), 'updated_at' => now()]);
    $accountId = DB::table('financial_accounts')->where('school_id', $school->id)->value('id');
    $ledgerId = DB::table('ledger_accounts')->where('school_id', $school->id)->where('code', '5400')->value('id');
    $costCentreId = DB::table('cost_centres')->where('school_id', $school->id)->value('id');
    $fundId = DB::table('accounting_funds')->where('school_id', $school->id)->value('id');
    DB::table('expenses')->insert(['school_id' => $school->id, 'term_id' => $termId, 'financial_account_id' => $accountId, 'supplier_id' => $supplierId, 'expense_ledger_account_id' => $ledgerId, 'cost_centre_id' => $costCentreId, 'fund_id' => $fundId, 'settlement_type' => 'immediate', 'category' => 'Utilities', 'amount' => 100, 'expense_date' => now()->toDateString(), 'created_at' => now(), 'updated_at' => now()]);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->delete(route('platform.schools.destroy', $school), ['school_number' => $school->school_number])
        ->assertRedirect(route('platform.schools'));

    $this->assertDatabaseMissing('schools', ['id' => $school->id]);
});

it('protects active customer schools from deletion', function () {
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'Customer School', 'slug' => 'customer-school', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'active', 'is_demo' => false]);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->from(route('platform.schools.show', $school))
        ->delete(route('platform.schools.destroy', $school), ['school_number' => $school->school_number])
        ->assertSessionHasErrors('school_number');

    $this->assertDatabaseHas('schools', ['id' => $school->id]);
});

it('updates school contact details and audits the change', function () {
    $admin = platformSchoolAdmin();
    $school = School::create(['name' => 'Old School', 'slug' => 'old-school', 'school_type' => 'secondary', 'status' => 'active', 'license_plan' => 'basic', 'license_status' => 'active']);

    $this->actingAs($admin, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->put(route('platform.schools.update', $school), [
            'name' => 'Updated School',
            'school_type' => 'secondary',
            'email' => 'office@updated.test',
            'status' => 'active',
            'is_demo' => '0',
        ])
        ->assertRedirect(route('platform.schools.show', $school));

    $this->assertDatabaseHas('schools', ['id' => $school->id, 'name' => 'Updated School', 'email' => 'office@updated.test']);
    expect(PlatformAuditLog::where('event', 'platform.school.updated')->exists())->toBeTrue();
});
it('converts an expired demo into an active school when its licence is activated', function () {
    $admin = platformSchoolAdmin();
    $school = School::create([
        'name' => 'Renewed Demo School',
        'slug' => 'renewed-demo-school',
        'school_type' => 'primary',
        'status' => 'demo',
        'is_demo' => true,
        'demo_expires_at' => now()->subDays(5),
        'license_plan' => 'basic',
        'license_status' => 'expired',
        'license_expires_at' => now()->subDay(),
    ]);

    $this->actingAs($admin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->patch(route('platform.licences.update', $school), [
            'license_plan' => 'premium',
            'license_status' => 'active',
            'license_started_at' => now()->toDateString(),
            'license_expires_at' => now()->addYear()->toDateString(),
        ])
        ->assertSessionHasNoErrors();

    $school->refresh();

    expect($school->license_status)->toBe('active')
        ->and($school->status)->toBe('active')
        ->and($school->is_demo)->toBeFalse()
        ->and($school->demo_expires_at)->toBeNull()
        ->and($school->isLicenceUsable())->toBeTrue()
        ->and($school->isExpiredDemo())->toBeFalse();
});
it('creates a school and its first administrator together', function () {
    $admin = platformSchoolAdmin();

    $response = $this->actingAs($admin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->post(route('platform.schools.store'), [
            'name' => 'New Trial Academy',
            'school_type' => 'secondary',
            'email' => 'office@newtrial.test',
            'license_plan' => 'basic',
            'license_status' => 'trial',
            'license_started_at' => now()->toDateString(),
            'license_expires_at' => now()->addDays(10)->toDateString(),
            'admin_name' => 'School Administrator',
            'admin_email' => 'admin@newtrial.test',
            'admin_password' => 'Temporary123!',
            'admin_password_confirmation' => 'Temporary123!',
        ]);

    $school = School::where('name', 'New Trial Academy')->firstOrFail();
    $response->assertRedirect(route('platform.schools.show', $school));

    $this->assertDatabaseHas('schools', [
        'id' => $school->id,
        'license_status' => 'trial',
        'status' => 'demo',
        'is_demo' => true,
    ]);
    $this->assertDatabaseHas('users', [
        'school_id' => $school->id,
        'name' => 'School Administrator',
        'email' => 'admin@newtrial.test',
        'role' => 'admin',
    ]);

    expect($school->demo_expires_at?->equalTo($school->license_expires_at))->toBeTrue()
        ->and(PlatformAuditLog::where('event', 'platform.school.created')->exists())->toBeTrue();
});
