<?php

use App\Models\LandingPageSetting;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('describes Android and enables its download only for a configured HTTPS address', function () {
    $this->get(route('home'))->assertOk()->assertSee('Edlink for Android')
        ->assertSee('Download APK')->assertSee('Google Play')->assertSee('App Store')->assertDontSee('Android download coming soon');
    LandingPageSetting::updateOrCreate(['key' => 'android_download_url'], ['value' => 'https://downloads.edlink.test/edlink.apk']);
    $this->get(route('home'))->assertOk()->assertSee('Download APK')
        ->assertSee('href="https://downloads.edlink.test/edlink.apk"', false);
    LandingPageSetting::where('key', 'android_download_url')->update(['value' => 'javascript:alert(1)']);
    $this->get(route('home'))->assertOk()->assertSee('Download APK')
        ->assertDontSee('href="javascript:', false);
});

it('lets the website owner configure an Android download and rejects insecure URLs', function () {
    $admin = PlatformAdmin::create([
        'name' => 'Website Owner', 'email' => 'android-owner@edlink.test',
        'password' => 'StrongPassword!123', 'role' => 'platform_owner', 'is_active' => true,
    ]);
    $this->actingAs($admin, 'platform')
        ->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->put(route('platform.website.update'), [
            'android_download_url' => 'https://downloads.edlink.test/edlink.apk',
            'google_play_url' => 'https://play.google.com/store/apps/details?id=com.edlink.school',
            'app_store_url' => 'https://apps.apple.com/ug/app/test-app/id123456789',
        ])
        ->assertSessionHasNoErrors();
    expect(LandingPageSetting::where('key', 'android_download_url')->value('value'))->toBe('https://downloads.edlink.test/edlink.apk');
    $this->get(route('home'))->assertOk()
        ->assertSee('href="https://play.google.com/store/apps/details?id=com.edlink.school"', false)
        ->assertSee('href="https://apps.apple.com/ug/app/test-app/id123456789"', false);
    $this->put(route('platform.website.update'), ['android_download_url' => 'http://downloads.edlink.test/edlink.apk'])
        ->assertSessionHasErrors('android_download_url');
});
