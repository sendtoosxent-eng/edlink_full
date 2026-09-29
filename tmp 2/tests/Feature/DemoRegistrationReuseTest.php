<?php

use App\Models\DemoRegistration;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

it('allows an email with only a stale deleted-demo marker to register again', function () {
    DemoRegistration::create(['email' => 'reusable@example.test', 'used_at' => now()->subWeek()]);

    Volt::test('pages.auth.register')
        ->set('school_name', 'Fresh Demo School')
        ->set('school_type', 'primary')
        ->set('name', 'Demo Administrator')
        ->set('email', '  REUSABLE@example.test ')
        ->set('password', 'StrongPassword!123')
        ->set('password_confirmation', 'StrongPassword!123')
        ->call('goToStep2')
        ->assertSet('email', 'reusable@example.test')
        ->assertSet('step', 2)
        ->assertHasNoErrors('email');
});

it('still prevents another demo while the email belongs to an existing tenant', function () {
    $school = School::create(['name' => 'Existing Demo', 'slug' => 'existing-demo', 'school_type' => 'primary']);
    User::factory()->create(['school_id' => $school->id, 'email' => 'claimed@example.test']);
    DemoRegistration::create(['email' => 'claimed@example.test', 'used_at' => now()]);

    Volt::test('pages.auth.register')
        ->set('school_name', 'Second Demo')
        ->set('school_type', 'primary')
        ->set('name', 'Another Administrator')
        ->set('email', 'claimed@example.test')
        ->set('password', 'StrongPassword!123')
        ->set('password_confirmation', 'StrongPassword!123')
        ->call('goToStep2')
        ->assertSet('step', 1)
        ->assertHasErrors('email');
});
