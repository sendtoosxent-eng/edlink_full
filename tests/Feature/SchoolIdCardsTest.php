<?php

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Database\Seeders\TeacherSubjectVisibilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);
    $this->school = School::where('school_number', 'EDL-TEACH')->firstOrFail();
    $this->admin = User::where('school_id', $this->school->id)->where('email', 'admin@edlink.local')->firstOrFail();
});

it('lets an administrator filter student and staff card candidates', function () {
    $this->actingAs($this->admin)->get(route('students.id-cards', ['type' => 'student', 'search' => 'P5-001']))
        ->assertOk()->assertSee('Amina Class Learner')->assertDontSee('Brian Subject Learner');

    $this->actingAs($this->admin)->get(route('students.id-cards', ['type' => 'staff', 'role' => 'bursar']))
        ->assertOk()->assertSee('Demo School Bursar')->assertDontSee('Demo Class Teacher');
});

it('generates a branded PDF for selected school records only', function () {
    $student = Student::where('school_id', $this->school->id)->where('admission_no', 'P5-001')->firstOrFail();

    $this->actingAs($this->admin)->post(route('students.id-cards.generate'), [
        'type' => 'student', 'ids' => [$student->id],
    ])->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('does not generate cards for records from another school', function () {
    $otherSchool = School::create(['name' => 'Other School', 'school_type' => 'primary', 'slug' => 'other-school', 'status' => 'active']);
    $outsider = Student::create(['school_id' => $otherSchool->id, 'name' => 'Outside Learner', 'admission_no' => 'OUT-1', 'status' => 'active']);

    $this->actingAs($this->admin)->post(route('students.id-cards.generate'), [
        'type' => 'student', 'ids' => [$outsider->id],
    ])->assertNotFound();
});
