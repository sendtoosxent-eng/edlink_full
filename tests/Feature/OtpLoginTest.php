<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('takes an unverified user directly to their dashboard after a valid emailed otp', function () {
    $school = School::create([
        'name' => 'OTP School',
        'slug' => 'otp-school',
        'status' => 'active',
        'license_status' => 'active',
    ]);
    $user = User::factory()->unverified()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'employment_status' => 'active',
    ]);
    $code = $user->generateOtp();

    $this->withSession([
        'otp_pending_user_id' => $user->id,
        'otp_remember' => true,
    ]);

    Volt::test('pages.auth.otp-verify')
        ->set('code', $code)
        ->call('verify')
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->hasVerifiedEmail())->toBeTrue()
        ->and(session('active_school_id'))->toBe($school->id)
        ->and(session('otp_pending_user_id'))->toBeNull();
});
