<?php

use App\Livewire\StudentEditModal;
use App\Livewire\StudentRegister;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function securitySchool(string $name): School
{
    return School::create(['name' => $name, 'slug' => str($name)->slug().'-'.str()->random(6)]);
}

it('does not expose the platform-wide database backup to school routes', function () {
    expect(Route::has('settings.backup'))->toBeFalse()
        ->and(Route::has('platform.backups.download'))->toBeTrue();
});

it('rejects class and category identifiers belonging to another school', function () {
    $home = securitySchool('Home School');
    $other = securitySchool('Other School');
    $admin = User::factory()->create(['school_id' => $home->id, 'role' => 'admin']);
    $foreignClass = SchoolClass::create(['school_id' => $other->id, 'name' => 'Foreign Class']);
    $foreignCategory = StudentCategory::create(['school_id' => $other->id, 'name' => 'Foreign Category']);

    Livewire::actingAs($admin)->test(StudentRegister::class)
        ->set('school_class_id', (string) $foreignClass->id)
        ->set('student_category_id', (string) $foreignCategory->id)
        ->call('goToStep3')
        ->assertHasErrors(['school_class_id', 'student_category_id']);
});

it('cannot update a guardian by tampering with a livewire guardian id', function () {
    $home = securitySchool('Home School');
    $other = securitySchool('Other School');
    $admin = User::factory()->create(['school_id' => $home->id, 'role' => 'admin']);
    $class = SchoolClass::create(['school_id' => $home->id, 'name' => 'Primary One']);
    $category = StudentCategory::create(['school_id' => $home->id, 'name' => 'Day']);
    $student = Student::create(['school_id' => $home->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'name' => 'Home Student', 'status' => 'active']);
    $ownGuardian = StudentGuardian::create(['student_id' => $student->id, 'name' => 'Own Guardian', 'is_primary' => true]);
    $foreignStudent = Student::create(['school_id' => $other->id, 'name' => 'Foreign Student', 'status' => 'active']);
    $foreignGuardian = StudentGuardian::create(['student_id' => $foreignStudent->id, 'name' => 'Foreign Guardian', 'is_primary' => true]);

    expect(fn () => Livewire::actingAs($admin)->test(StudentEditModal::class)
        ->call('open', $student->id)
        ->set('guardianId', $foreignGuardian->id)
        ->set('guardian_name', 'Compromised')
        ->call('save'))->toThrow(ModelNotFoundException::class);

    expect($foreignGuardian->fresh()->name)->toBe('Foreign Guardian')
        ->and($ownGuardian->fresh()->name)->toBe('Own Guardian');
});
