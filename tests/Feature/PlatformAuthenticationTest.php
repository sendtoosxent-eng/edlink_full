<?php

use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use App\Services\PlatformTotpService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);


it('renders database-backed landing page content and assets', function () {
    \App\Models\LandingPageSetting::updateOrCreate(['key' => 'hero_title'], ['value' => 'A database powered school platform']);
    \App\Models\LandingPageSetting::updateOrCreate(['key' => 'hero_image'], ['value' => 'img/hero.png']);
    \App\Models\LandingPageSetting::updateOrCreate(['key' => 'facebook_url'], ['value' => 'https://facebook.com/edlink']);

    $this->get(route('home'))
        ->assertOk()
        ->assertSee('A database powered school platform')
        ->assertSee(asset('img/hero.png'), false)
        ->assertSee('https://facebook.com/edlink', false)
        ->assertSee('Follow Edlink on Facebook');
});
it('updates and renders every landing page image from the platform editor', function () {
    Storage::fake('public');
    $admin = PlatformAdmin::create([
        'name' => 'Website Owner',
        'email' => 'website@edlink.test',
        'password' => 'StrongPassword!123',
        'role' => 'platform_owner',
        'is_active' => true,
    ]);
    $images = collect(\App\Models\LandingPageSetting::ASSET_KEYS)
        ->mapWithKeys(fn ($key) => [$key => UploadedFile::fake()->image($key.'.png')])
        ->all();

    $response = $this->actingAs($admin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->put(route('platform.website.update'), $images);

    $response->assertSessionHasNoErrors()->assertSessionHas('status');
    foreach (array_keys($images) as $key) {
        $path = \App\Models\LandingPageSetting::where('key', $key)->value('value');
        expect($path)->toStartWith('landing-page/');
        Storage::disk('public')->assertExists($path);
    }

    $landing = \App\Models\LandingPageSetting::values();
    $this->get(route('home'))
        ->assertOk()
        ->assertSee(\App\Models\LandingPageSetting::assetUrl($landing, 'feature_image'), false);
});
it('shows the separate Edlink platform login', function () {
    $this->get(route('platform.login'))->assertOk()->assertSee('Platform access')->assertSee('Password verification is followed by MFA');
});

it('requires password then enrolls a first-time platform owner in TOTP', function () {
    $admin=PlatformAdmin::create(['name'=>'Platform Owner','email'=>'owner@edlink.test','password'=>'StrongPassword!123','role'=>'platform_owner','is_active'=>true]);
    $this->post(route('platform.login.store'),['email'=>$admin->email,'password'=>'StrongPassword!123'])->assertRedirect(route('platform.setup'));
    $this->get(route('platform.setup'))->assertOk()->assertSee('Connect your authenticator')->assertSee('data:image/svg+xml;base64',false);
    $admin->refresh(); expect($admin->totp_secret)->not->toBeNull();
    $code=(new Google2FA)->getCurrentOtp($admin->totp_secret);
    $this->post(route('platform.setup.confirm'),['code'=>$code])->assertOk()->assertSee('MFA enabled')->assertSee('Save your recovery codes');
    $admin->refresh(); expect($admin->totp_confirmed_at)->not->toBeNull()->and($admin->recovery_codes)->toHaveCount(10)->and($admin->last_totp_hash)->toBeNull();
    expect(PlatformAuditLog::where('event','platform.totp.enabled')->where('platform_admin_id',$admin->id)->exists())->toBeTrue();
});

it('allows a reasonable amount of authenticator clock drift', function () {
    $totp = new Google2FA;
    $secret = app(PlatformTotpService::class)->generateSecret();
    $slightlyOldCode = $totp->oathTotp($secret, $totp->getTimestamp() - 2);

    expect(app(PlatformTotpService::class)->verify($secret, $slightlyOldCode))->toBeTrue();
});

it('requires a valid TOTP before entering the platform dashboard', function () {
    $secret=app(PlatformTotpService::class)->generateSecret();
    $admin=PlatformAdmin::create(['name'=>'Secure Owner','email'=>'secure@edlink.test','password'=>'StrongPassword!123','role'=>'platform_owner','is_active'=>true,'totp_secret'=>$secret,'totp_confirmed_at'=>now(),'recovery_codes'=>[]]);
    $this->post(route('platform.login.store'),['email'=>$admin->email,'password'=>'StrongPassword!123'])->assertRedirect(route('platform.challenge'));
    $this->get(route('platform.dashboard'))->assertRedirect(route('platform.challenge'));
    $code=(new Google2FA)->getCurrentOtp($secret);
    $this->post(route('platform.challenge.verify'),['code'=>$code])->assertRedirect(route('platform.dashboard'));
    $this->get(route('platform.dashboard'))
        ->assertOk()
        ->assertSee('Platform Dashboard')
        ->assertSee('Secure session')
        ->assertSee('Recently Registered Schools');
    $this->get(route('platform.schools'))->assertOk()->assertSee('Manage Subscriptions');
    $this->get(route('platform.licences'))->assertOk()->assertSeeText('Packages & School Capacity');
    $this->get(route('platform.billing'))->assertOk()->assertSeeText('Billing & renewal control');
    $this->get(route('platform.audit'))->assertOk()->assertSeeText('Platform audit trail');
    $this->get(route('platform.backups'))->assertOk()->assertSeeText('Database backup centre')->assertSeeText('No automated backup has run yet');
    $this->get(route('platform.website.edit'))->assertOk()->assertSee('Landing Page Content');
    expect(PlatformAuditLog::where('event','platform.login.succeeded')->where('platform_admin_id',$admin->id)->exists())->toBeTrue();
});

it('accepts each hashed recovery code only once', function () {
    $secret=app(PlatformTotpService::class)->generateSecret(); $recovery='ABCD-1234-EF56';
    $admin=PlatformAdmin::create(['name'=>'Recovery Owner','email'=>'recovery@edlink.test','password'=>'StrongPassword!123','role'=>'platform_owner','is_active'=>true,'totp_secret'=>$secret,'totp_confirmed_at'=>now(),'recovery_codes'=>[Hash::make($recovery)]]);
    $this->post(route('platform.login.store'),['email'=>$admin->email,'password'=>'StrongPassword!123']);
    $this->post(route('platform.challenge.verify'),['code'=>$recovery])->assertRedirect(route('platform.dashboard'));
    expect($admin->fresh()->recovery_codes)->toBe([]);
});

it('lets an authenticated platform administrator securely reset MFA', function () {
    $admin=PlatformAdmin::create(['name'=>'Reset Owner','email'=>'reset@edlink.test','password'=>'StrongPassword!123','role'=>'platform_owner','is_active'=>true,'totp_secret'=>app(PlatformTotpService::class)->generateSecret(),'totp_confirmed_at'=>now(),'recovery_codes'=>[Hash::make('ABCD-1234-EF56')],'last_totp_hash'=>'old-hash']);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.mfa.reset'))
        ->assertOk()
        ->assertSee('Reset MFA');

    $this->actingAs($admin, 'platform')
        ->post(route('platform.mfa.reset.store'), ['password'=>'StrongPassword!123','confirmation'=>'RESET MFA'])
        ->assertRedirect(route('platform.setup'));

    $admin->refresh();
    expect($admin->totp_secret)->toBeNull()
        ->and($admin->totp_confirmed_at)->toBeNull()
        ->and($admin->recovery_codes)->toBe([])
        ->and($admin->last_totp_hash)->toBeNull();
    expect(PlatformAuditLog::where('event','platform.mfa.reset')->where('platform_admin_id',$admin->id)->exists())->toBeTrue();
});
