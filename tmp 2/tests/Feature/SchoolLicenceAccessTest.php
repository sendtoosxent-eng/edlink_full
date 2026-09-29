<?php

use App\Http\Middleware\EnsureSchoolLicenceIsActive;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function expiredSchoolUser(): array
{
    $school = School::create([
        'name' => 'Expired Academy',
        'slug' => 'expired-academy',
        'school_number' => 'EDL-EXPIRED',
        'status' => 'active',
        'license_status' => 'active',
        'license_expires_at' => now()->subDay(),
    ]);

    $user = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'email' => 'admin@expired.test',
        'password' => 'password',
    ]);

    return [$school, $user];
}

it('rejects login for every user mapped to an expired school number', function () {
    [$school, $user] = expiredSchoolUser();

    Volt::test('pages.auth.login')
        ->set('school_number', $school->school_number)
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasErrors(['email' => EnsureSchoolLicenceIsActive::EXPIRED_MESSAGE]);

    expect(Auth::check())->toBeFalse();
});

it('logs out an existing session when its school licence has expired', function () {
    [, $user] = expiredSchoolUser();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors(['email' => EnsureSchoolLicenceIsActive::EXPIRED_MESSAGE]);

    expect(Auth::check())->toBeFalse();
});