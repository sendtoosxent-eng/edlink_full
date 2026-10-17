<?php

use App\Livewire\StudentRegister;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('completes the student registration wizard and generates an identifier', function () {
    $school = School::create(['name' => 'Registration Test School', 'slug' => 'student-registration-test']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term One', 'term_number' => 1, 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary One']);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    Livewire::actingAs($admin)->test(StudentRegister::class)
        ->set('name', 'New Learner')
        ->set('lin', 'lin-2026-0042')
        ->call('goToStep2')->assertHasNoErrors()->assertSet('step', 2)
        ->set('school_class_id', (string) $class->id)
        ->set('student_category_id', (string) $category->id)
        ->call('goToStep3')->assertHasNoErrors()->assertSet('step', 3)
        ->set('guardian_name', 'Primary Guardian')
        ->call('goToStep4')->assertHasNoErrors()->assertSet('step', 4)
        ->call('register')->assertHasNoErrors()->assertRedirect(route('students.index'));

    $student = Student::where('school_id', $school->id)->where('name', 'New Learner')->firstOrFail();

    expect($student->admission_no)->toBe('STU-'.$school->id.'-'.str_pad((string) $student->id, 6, '0', STR_PAD_LEFT))
        ->and($student->lin)->toBe('LIN-2026-0042')
        ->and($student->term_id)->toBe($term->id)
        ->and($student->enrolments()->where('term_id', $term->id)->exists())->toBeTrue();
});

it('requires a learner identification number to be unique within its school', function () {
    $school = School::create(['name' => 'LIN Test School', 'slug' => 'lin-test-school']);
    $otherSchool = School::create(['name' => 'Other LIN School', 'slug' => 'other-lin-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    Student::create([
        'school_id' => $school->id,
        'name' => 'Existing Learner',
        'lin' => 'LIN-001',
        'status' => 'active',
    ]);
    Student::create([
        'school_id' => $otherSchool->id,
        'name' => 'Other School Learner',
        'lin' => 'LIN-001',
        'status' => 'active',
    ]);

    Livewire::actingAs($admin)->test(StudentRegister::class)
        ->set('name', 'Duplicate LIN Learner')
        ->set('lin', 'lin-001')
        ->call('goToStep2')
        ->assertHasErrors(['lin' => 'unique'])
        ->assertSet('step', 1);
});
