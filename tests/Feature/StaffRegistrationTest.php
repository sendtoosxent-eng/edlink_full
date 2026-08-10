<?php

use App\Livewire\StaffRegister;
use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use App\Notifications\QueuedVerifyEmail;
use App\Models\AuditLog;

uses(RefreshDatabase::class);

it('advances through every step and registers a staff account', function () {
    Notification::fake();
    $school = School::create(['name' => 'Registration Test School', 'slug' => 'registration-test-school']);
    $designation = Designation::create(['school_id' => $school->id, 'name' => 'Subject Teacher', 'permissions' => ['academics.subjects']]);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    Livewire::actingAs($admin)->test(StaffRegister::class)
        ->set('name', 'New Staff Member')
        ->set('email', 'new.staff@example.test')
        ->set('phone', '+256700111222')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('photo', UploadedFile::fake()->image('staff.jpg'))
        ->call('next')->assertHasNoErrors()->assertSet('step', 2)
        ->set('job_title', 'Teacher')
        ->set('role', 'teacher')
        ->set('designation_id', (string) $designation->id)
        ->call('next')->assertHasNoErrors()->assertSet('step', 3)
        ->set('base_salary', '450000')
        ->set('emergency_contact_name', 'Grace Member')
        ->set('emergency_contact_phone', '+256700333444')
        ->set('contract_type', 'contract')
        ->set('document_type', 'Contract')
        ->set('document_file', UploadedFile::fake()->create('contract.pdf', 100, 'application/pdf'))
        ->call('register')->assertHasNoErrors()->assertRedirect(route('staff.index'));

    $staff = User::where('email', 'new.staff@example.test')->firstOrFail();
    $this->assertDatabaseHas('users', [
        'school_id' => $school->id, 'email' => 'new.staff@example.test',
        'designation_id' => $designation->id, 'role' => 'teacher',
        'staff_number' => 'STF-'.$school->id.'-'.str_pad((string) $staff->id, 6, '0', STR_PAD_LEFT),
        'emergency_contact_name' => 'Grace Member', 'contract_type' => 'contract',
    ]);
    expect($staff->avatar_path)->not->toBeNull();
    expect(Hash::check('Password123!', $staff->password))->toBeTrue();
    Notification::assertSentTo($staff, QueuedVerifyEmail::class);
    expect(AuditLog::where('event', 'staff.registered')->where('subject_id', $staff->id)->exists())->toBeTrue();
});

it('rejects duplicate email within a school but permits it in another school', function () {
    $school = School::create(['name' => 'First School', 'slug' => 'first-school']);
    $otherSchool = School::create(['name' => 'Second School', 'slug' => 'second-school']);
    $designation = Designation::create(['school_id' => $school->id, 'name' => 'Teacher', 'permissions' => []]);
    $otherDesignation = Designation::create(['school_id' => $otherSchool->id, 'name' => 'Teacher', 'permissions' => []]);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    User::factory()->create(['school_id' => $school->id, 'email' => 'shared@example.test']);

    Livewire::actingAs($admin)->test(StaffRegister::class)
        ->set('name', 'Duplicate')
        ->set('email', 'shared@example.test')
        ->set('password', 'Password123!')->set('password_confirmation', 'Password123!')
        ->call('next')->assertHasErrors(['email']);

    $otherAdmin = User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'admin']);
    Livewire::actingAs($otherAdmin)->test(StaffRegister::class)
        ->set('name', 'Shared Staff')->set('email', 'shared@example.test')
        ->set('password', 'Password123!')->set('password_confirmation', 'Password123!')
        ->set('designation_id', (string) $otherDesignation->id)->call('next')
        ->set('designation_id', (string) $otherDesignation->id)->call('next')
        ->set('base_salary', '1')->call('register')->assertHasNoErrors();
});

it('rejects a designation belonging to another school', function () {
    $school = School::create(['name' => 'Safe School', 'slug' => 'safe-school']);
    $other = School::create(['name' => 'Other School', 'slug' => 'other-school']);
    $foreignDesignation = Designation::create(['school_id' => $other->id, 'name' => 'Foreign', 'permissions' => []]);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    Livewire::actingAs($admin)->test(StaffRegister::class)
        ->set('name', 'Unsafe Staff')->set('email', 'unsafe@example.test')
        ->set('password', 'Password123!')->set('password_confirmation', 'Password123!')
        ->set('job_title', 'Teacher')->set('role', 'teacher')->set('designation_id', (string) $foreignDesignation->id)
        ->set('base_salary', '1')->call('register')->assertHasErrors(['designation_id']);
});

it('requires confirmation before creating an administrator', function () {
    $school = School::create(['name' => 'Admin School', 'slug' => 'admin-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    Livewire::actingAs($admin)->test(StaffRegister::class)
        ->set('name', 'New Administrator')->set('email', 'new.admin@example.test')
        ->set('password', 'Password123!')->set('password_confirmation', 'Password123!')
        ->set('role', 'admin')->set('base_salary', '1')->call('register')
        ->assertHasErrors(['admin_confirmation']);
});

it('does not allow an unauthorized staff member to open registration', function () {
    $school = School::create(['name' => 'Authorization School', 'slug' => 'authorization-school']);
    $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);

    $this->actingAs($teacher)->get(route('staff.register'))->assertForbidden();
});
