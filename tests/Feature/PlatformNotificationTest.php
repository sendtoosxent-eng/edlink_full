<?php

use App\Models\{PlatformAdmin, PlatformNotification, School, User};
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows owners only their notifications and lets them mark their own alerts read', function () {
    $owners = collect(['one', 'two'])->map(fn ($key) => PlatformAdmin::create(['name' => 'Owner '.$key, 'email' => $key.'@edlink.test', 'password' => 'StrongPassword!123', 'role' => 'platform_owner', 'is_active' => true]));
    $alerts = $owners->map(fn ($owner) => PlatformNotification::create(['platform_admin_id' => $owner->id, 'event_key' => 'test', 'title' => 'Private alert '.$owner->id, 'message' => 'Log needs attention']));
    $this->actingAs($owners[0], 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->get(route('platform.notifications.index'))->assertOk()->assertSee($alerts[0]->title)->assertDontSee($alerts[1]->title);
    $this->patch(route('platform.notifications.read', $alerts[1]))->assertNotFound();
    $this->patch(route('platform.notifications.read', $alerts[0]))->assertRedirect();
    expect($alerts[0]->fresh()->read_at)->not->toBeNull();
    expect($alerts[1]->fresh()->read_at)->toBeNull();
});

it('denies non-owners access to platform health notifications', function () {
    $operator = PlatformAdmin::create(['name' => 'Operations', 'email' => 'ops@edlink.test', 'password' => 'StrongPassword!123', 'role' => 'operations_admin', 'is_active' => true]);
    $this->actingAs($operator, 'platform')->withSession(['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp])
        ->get(route('platform.notifications.index'))->assertForbidden();
});
