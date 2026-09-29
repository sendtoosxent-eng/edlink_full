<?php

use App\Livewire\StaffRegister;
use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('advances through every step and registers a staff account', function () {
    $school = School::create(['name' => 'Registration Test School', 'slug' => 'registration-test-school']);
    $designation = Designation::create(['school_id' => $school->id, 'name' => 'Subject Teacher', 'permissions' => ['academics.subjects']]);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    Livewire::actingAs($admin)->test(StaffRegister::class)
        ->set('name', 'New Staff Member')
        ->set('email', 'new.staff@example.test')
        ->set('phone', '+256700111222')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('next')->assertHasNoErrors()->assertSet('step', 2)
        ->set('job_title', 'Teacher')
        ->set('role', 'teacher')
        ->set('designation_id', (string) $designation->id)
        ->call('next')->assertHasNoErrors()->assertSet('step', 3)
        ->set('base_salary', '450000')
        ->call('register')->assertHasNoErrors()->assertRedirect(route('staff.index'));

    $this->assertDatabaseHas('users', [
        'school_id' => $school->id, 'email' => 'new.staff@example.test',
        'designation_id' => $designation->id, 'role' => 'teacher',
    ]);
});
