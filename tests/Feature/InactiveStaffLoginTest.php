<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('rejects inactive staff at web login', function () {
    $school = School::create(['name' => 'Inactive Staff School', 'slug' => 'inactive-staff-school']);
    User::factory()->create([
        'school_id' => $school->id,
        'email' => 'inactive@example.test',
        'password' => Hash::make('Password123!'),
        'role' => 'teacher',
        'employment_status' => 'inactive',
    ]);

    Livewire::test('pages.auth.login')
        ->set('school_number', $school->school_number)
        ->set('email', 'inactive@example.test')
        ->set('password', 'Password123!')
        ->call('login')
        ->assertHasErrors(['email']);

    expect(auth()->check())->toBeFalse();
});
