<?php

use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\SchoolSmsConfiguration;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function smsPlatformOwner(): PlatformAdmin
{
    return PlatformAdmin::create([
        'name' => 'SMS Platform Owner',
        'email' => 'sms-owner@edlink.test',
        'password' => 'password',
        'role' => 'platform_owner',
        'is_active' => true,
    ]);
}

it('configures SMS independently for one school and encrypts its secrets', function () {
    $admin = smsPlatformOwner();
    $first = School::create(['name' => 'SMS School', 'slug' => 'sms-school', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'active']);
    $second = School::create(['name' => 'No SMS School', 'slug' => 'no-sms-school', 'school_type' => 'primary', 'license_plan' => 'basic', 'license_status' => 'active']);

    $this->actingAs($admin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->put(route('platform.schools.sms-configuration.update', $first), [
            'enabled' => '1',
            'provider' => 'africastalking',
            'api_username' => 'sandbox',
            'api_key' => 'super-secret-api-key',
            'sender_id' => 'EDLINK',
            'webhook_secret' => 'delivery-secret',
        ])->assertSessionHasNoErrors();

    $configuration = SchoolSmsConfiguration::where('school_id', $first->id)->firstOrFail();
    expect($configuration->enabled)->toBeTrue()
        ->and($configuration->api_key)->toBe('super-secret-api-key')
        ->and($configuration->isReady())->toBeTrue()
        ->and($second->smsConfiguration)->toBeNull();

    $raw = DB::table('school_sms_configurations')->where('school_id', $first->id)->first();
    expect($raw->api_key)->not->toBe('super-secret-api-key')
        ->and($raw->webhook_secret)->not->toBe('delivery-secret')
        ->and(PlatformAuditLog::where('event', 'platform.school.sms_configuration.updated')->exists())->toBeTrue();
});

it('refuses to enable SMS without the required credentials', function () {
    $admin = smsPlatformOwner();
    $school = School::create(['name' => 'Incomplete SMS School', 'slug' => 'incomplete-sms', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'active']);

    $this->actingAs($admin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->put(route('platform.schools.sms-configuration.update', $school), [
            'enabled' => '1',
            'provider' => 'africastalking',
            'sender_id' => 'EDLINK',
        ])->assertSessionHasErrors(['api_key']);

    expect($school->smsConfiguration)->toBeNull();
});

it('shows the SMS controls only behind platform MFA', function () {
    $admin = smsPlatformOwner();
    $school = School::create(['name' => 'Visible SMS School', 'slug' => 'visible-sms', 'school_type' => 'secondary', 'license_plan' => 'basic', 'license_status' => 'active']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.schools.show', $school))
        ->assertRedirect(route('platform.challenge'));

    $this->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->get(route('platform.schools.show', $school))
        ->assertOk()
        ->assertSee('SMS gateway')
        ->assertSee('Allow this school to send SMS');
});
