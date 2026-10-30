<?php

use App\Models\LandingPageSetting;
use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function signInWebsiteOwner($test): void
{
    $owner = PlatformAdmin::create([
        'name' => 'Website Owner', 'email' => 'content-owner@edlink.test',
        'password' => 'StrongPassword!123', 'role' => 'platform_owner', 'is_active' => true,
    ]);
    $test->actingAs($owner, 'platform')->withSession([
        'platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp,
    ]);
}

it('publishes the complete editor payload and preserves unrelated content on partial edits', function () {
    signInWebsiteOwner($this);
    $this->get(route('platform.website.edit'))->assertOk()
        ->assertSee('Publish Changes')->assertSee('Yellow logo for dark mode')
        ->assertSee('name="pricing_ugx_150k"', false)->assertSee('name="testimonial_portrait_1"', false);

    $payload = array_diff_key(LandingPageSetting::defaults(), array_flip(LandingPageSetting::assetKeys()));
    $payload['pricing_ugx_150k'] = 'UGX 200k';
    $payload['desktop_your_school'] = 'Your new desktop workspace.';
    $payload['testimonials_sarah_namusoke'] = 'Approved reviewer';
    $payload['role_administrator_label'] = 'School leadership';
    $payload['navigation_demo_access'] = '#pricing';
    $this->put(route('platform.website.update'), $payload)->assertSessionHasNoErrors()->assertRedirect();
    $this->get(route('home'))->assertOk()->assertSee('UGX 200k')->assertSee('Your new desktop workspace.')
        ->assertSee('Approved reviewer')->assertSee('School leadership')->assertSee('href="#pricing"', false);

    $this->put(route('platform.website.update'), ['hero_title' => '<script>alert(1)</script>'])
        ->assertSessionHasNoErrors();
    $this->get(route('home'))->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
        ->assertDontSee('<script>alert(1)</script>', false)->assertSee('UGX 200k');
});

it('rejects unsafe links without saving any submitted content', function () {
    signInWebsiteOwner($this);
    $original = LandingPageSetting::where('key', 'hero_title')->value('value');
    foreach (['javascript:alert(1)', '//untrusted.test', '/\\untrusted.test', 'http://insecure.test'] as $link) {
        $this->put(route('platform.website.update'), [
            'hero_title' => 'Should not be saved', 'navigation_demo_access' => $link,
        ])->assertSessionHasErrors('navigation_demo_access');
    }
    expect(LandingPageSetting::where('key', 'hero_title')->value('value'))->toBe($original);
});

it('uploads newly editable illustrations and serves them through the landing asset endpoint', function () {
    Storage::fake('public');
    signInWebsiteOwner($this);
    $this->put(route('platform.website.update'), [
        'hero_image_1' => UploadedFile::fake()->image('hero.png', 50, 50),
        'testimonial_portrait_1' => UploadedFile::fake()->image('portrait.png', 50, 50),
    ])->assertSessionHasNoErrors();
    $this->get('/media/landing/hero_image_1')->assertOk()->assertHeader('content-type', 'image/png');
    $this->get('/media/landing/testimonial_portrait_1')->assertOk();
    $this->get(route('home'))->assertOk()->assertSee('/media/landing/testimonial_portrait_1', false);
});

it('keeps the website editor and publishing behind platform authentication', function () {
    $original = LandingPageSetting::where('key', 'hero_title')->value('value');
    $this->get(route('platform.website.edit'))->assertRedirect(route('platform.login'));
    $this->put(route('platform.website.update'), ['hero_title' => 'Unauthorized change'])
        ->assertRedirect(route('platform.login'));
    expect(LandingPageSetting::where('key', 'hero_title')->value('value'))->toBe($original);
});
