<?php

use App\Livewire\Designations;
use App\Livewire\StaffManagement;
use App\Livewire\StaffRegister;
use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use App\Services\DefaultDesignationService;
use App\Support\DesignationPermissions;
use App\Support\TeacherAcademicScope;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Default Access School', 'slug' => 'default-access']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
});

it('provides nine different editable defaults and preserves renamed defaults and customised access', function () {
    $defaults = app(DefaultDesignationService::class)->ensureFor($this->school);
    expect($defaults)->toHaveCount(9)
        ->and($defaults->pluck('name')->all())->toEqualCanonicalizing(array_keys(DesignationPermissions::defaults()));
    $head = $defaults->firstWhere('default_key', 'head_teacher');
    $head->update(['name' => 'Principal', 'permissions' => ['staff.designations']]);
    $again = app(DefaultDesignationService::class)->ensureFor($this->school);
    expect($again)->toHaveCount(9)
        ->and($head->fresh()->name)->toBe('Principal')
        ->and($head->fresh()->permissions)->toBe(['staff.designations']);
    expect($defaults->firstWhere('default_key', 'super_administrator')->permissions)->toContain('accounting.journals.approve')
        ->and($defaults->firstWhere('default_key', 'administrator')->permissions)->not->toContain('accounting.journals.approve');
});

it('adopts the old DOS designation without losing assignments or customised permissions', function () {
    $dos = Designation::create(['school_id' => $this->school->id, 'name' => 'DOS', 'permissions' => ['exams.setup']]);
    $staff = User::factory()->create(['school_id' => $this->school->id, 'role' => 'academic_admin', 'designation_id' => $dos->id]);
    app(DefaultDesignationService::class)->ensureFor($this->school);
    expect($dos->fresh()->default_key)->toBe('dean_of_studies')
        ->and($dos->fresh()->name)->toBe('Dean of Studies')
        ->and($dos->fresh()->permissions)->toBe(['exams.setup'])
        ->and($staff->fresh()->designation_id)->toBe($dos->id);
});

it('allows administrators and delegated designation managers to create and edit access', function () {
    Livewire::actingAs($this->admin)->test(Designations::class)
        ->set('name', 'Access Coordinator')->set('permissions', ['staff.designations'])
        ->call('save')->assertHasNoErrors();
    $managerDesignation = Designation::where('school_id', $this->school->id)->where('name', 'Access Coordinator')->firstOrFail();
    $manager = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar', 'designation_id' => $managerDesignation->id]);
    $this->actingAs($manager)->get(route('designations.index'))->assertOk();
    $head = Designation::where('school_id', $this->school->id)->where('default_key', 'head_of_subject')->firstOrFail();
    $subjectHead = User::factory()->create(['school_id' => $this->school->id, 'role' => 'academic_admin', 'designation_id' => $head->id]);
    expect($subjectHead->hasPermission('finance.payments'))->toBeFalse();
    Livewire::actingAs($manager)->test(Designations::class)->call('edit', $head->id)
        ->set('permissions', ['finance.payments'])->call('save')->assertHasNoErrors();
    expect($subjectHead->fresh()->hasPermission('finance.payments'))->toBeTrue();
    Livewire::actingAs($manager)->test(Designations::class)->set('name', 'Librarian')
        ->set('permissions', ['students.view'])->call('save')->assertHasNoErrors();
    $this->assertDatabaseHas('designations', ['school_id' => $this->school->id, 'name' => 'Librarian']);
});

it('applies access immediately on assignment without granting platform administrator status', function () {
    $defaults = app(DefaultDesignationService::class)->ensureFor($this->school);
    $staff = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    Livewire::actingAs($this->admin)->test(StaffManagement::class)
        ->call('assignDesignation', $staff->id, (string) $defaults->firstWhere('default_key', 'head_teacher')->id);
    $staff->refresh();
    expect($staff->hasPermission('staff.manage'))->toBeTrue()
        ->and($staff->hasPermission('finance.payments'))->toBeFalse()
        ->and(TeacherAcademicScope::isTeacher($staff))->toBeFalse();
    Livewire::actingAs($this->admin)->test(StaffManagement::class)
        ->call('assignDesignation', $staff->id, (string) $defaults->firstWhere('default_key', 'super_administrator')->id);
    $staff->refresh();
    expect($staff->hasPermission('staff.designations'))->toBeTrue()
        ->and($staff->hasPermission('accounting.journals.approve'))->toBeTrue()
        ->and($staff->isSuperadmin())->toBeFalse();
});

it('uses stable default identities during registration including head teachers and renamed designations', function () {
    $component = Livewire::actingAs($this->admin)->test(StaffRegister::class);
    $head = Designation::where('school_id', $this->school->id)->where('default_key', 'head_teacher')->firstOrFail();
    $component->set('designation_id', (string) $head->id)
        ->assertSet('role', 'academic_admin')->assertSet('has_teaching_duties', false);
    $teacher = Designation::where('school_id', $this->school->id)->where('default_key', 'class_teacher')->firstOrFail();
    $teacher->update(['name' => 'Class Mentor']);
    $component->set('designation_id', (string) $teacher->id)
        ->assertSet('role', 'teacher')->assertSet('has_teaching_duties', true)->assertSet('is_class_teacher', true);
});

it('applies a default designation consistently from the staff editing form', function () {
    $defaults = app(DefaultDesignationService::class)->ensureFor($this->school);
    $staff = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher', 'job_title' => 'Teacher']);
    Livewire::actingAs($this->admin)->test(StaffManagement::class)->call('edit', $staff->id)
        ->set('editDesignationId', (string) $defaults->firstWhere('default_key', 'head_of_department')->id)
        ->call('updateStaff')->assertHasNoErrors();
    expect($staff->fresh()->role)->toBe('academic_admin')
        ->and($staff->fresh()->hasPermission('exams.setup'))->toBeTrue();
});

it('requires designation management access before assigning an access management designation', function () {
    $defaults = app(DefaultDesignationService::class)->ensureFor($this->school);
    $hr = Designation::create(['school_id' => $this->school->id, 'name' => 'HR', 'permissions' => ['staff.directory', 'staff.manage']]);
    $manager = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar', 'designation_id' => $hr->id]);
    $staff = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    Livewire::actingAs($manager)->test(StaffManagement::class)
        ->call('assignDesignation', $staff->id, (string) $defaults->firstWhere('default_key', 'super_administrator')->id);
    expect($staff->fresh()->designation_id)->toBeNull()
        ->and($staff->fresh()->hasPermission('staff.designations'))->toBeFalse();
});

it('blocks unauthorised editing invalid permissions and changes to another school', function () {
    $staff = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $this->actingAs($staff)->get(route('designations.index'))->assertForbidden();
    Livewire::actingAs($staff)->test(Designations::class)->assertForbidden();
    Livewire::actingAs($this->admin)->test(Designations::class)->set('name', 'Invalid access')
        ->set('permissions', ['invented.permission'])->call('save')->assertHasErrors(['permissions.0']);
    $otherSchool = School::create(['name' => 'Other Access School', 'slug' => 'other-access']);
    $foreign = Designation::create(['school_id' => $otherSchool->id, 'name' => 'Foreign designation', 'permissions' => []]);
    Livewire::actingAs($this->admin)->test(Designations::class)->call('edit', $foreign->id)->assertNotFound();
});

it('keeps defaults available while permitting deletion of unused custom designations', function () {
    $component = Livewire::actingAs($this->admin)->test(Designations::class);
    $default = Designation::where('school_id', $this->school->id)->where('default_key', 'bursar')->firstOrFail();
    $component->call('delete', $default->id);
    $this->assertDatabaseHas('designations', ['id' => $default->id]);
    $custom = Designation::create(['school_id' => $this->school->id, 'name' => 'Unused custom', 'permissions' => []]);
    $component->call('delete', $custom->id);
    $this->assertDatabaseMissing('designations', ['id' => $custom->id]);
});
