<?php

use App\Livewire\Attendance;
use App\Livewire\SubjectAttendance;
use App\Models\Designation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function mappedTeacherFixture(): array
{
    $school = School::create(['name' => 'Mapped Teacher School', 'slug' => 'mapped-teacher-school-'.uniqid()]);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => 2026, 'is_current' => true, 'status' => 'open', 'locked' => false]);
    $classA = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class A', 'education_stage' => 'primary', 'sort_order' => 1]);
    $classB = SchoolClass::create(['school_id' => $school->id, 'name' => 'Class B', 'education_stage' => 'primary', 'sort_order' => 2]);
    $studentA = Student::create(['school_id' => $school->id, 'school_class_id' => $classA->id, 'name' => 'Learner Class A', 'admission_no' => 'A-1', 'status' => 'active']);
    $studentB = Student::create(['school_id' => $school->id, 'school_class_id' => $classB->id, 'name' => 'Learner Class B', 'admission_no' => 'B-1', 'status' => 'active']);
    $math = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
    $designation = Designation::create(['school_id' => $school->id, 'name' => 'Senior Teacher', 'permissions' => []]);
    $teacher = User::factory()->create(['school_id' => $school->id, 'designation_id' => $designation->id, 'role' => 'teacher', 'employment_status' => 'active']);
    $classA->update(['class_teacher_user_id' => $teacher->id]);
    DB::table('staff_subjects')->insert([
        'school_id' => $school->id,
        'term_id' => $term->id,
        'user_id' => $teacher->id,
        'school_class_id' => $classB->id,
        'subject_id' => $math->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return compact('school', 'term', 'classA', 'classB', 'studentA', 'studentB', 'math', 'teacher');
}

it('derives teacher academic permissions from exact mappings', function () {
    $data = mappedTeacherFixture();
    $teacher = $data['teacher']->fresh();

    expect($teacher->hasPermission('attendance.daily'))->toBeTrue()
        ->and($teacher->hasPermission('attendance.subject'))->toBeTrue()
        ->and($teacher->hasPermission('exams.marks'))->toBeTrue()
        ->and($teacher->hasModuleAccess('attendance'))->toBeTrue()
        ->and($teacher->hasPermission('finance.payments'))->toBeFalse();
});

it('limits class attendance to students in the class-teacher assignment', function () {
    $data = mappedTeacherFixture();

    Livewire::actingAs($data['teacher'])->test(Attendance::class)
        ->assertSee('Learner Class A')
        ->assertDontSee('Learner Class B')
        ->set('statuses.'.$data['studentA']->id, 'present')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendance_records', [
        'student_id' => $data['studentA']->id,
        'recorded_by' => $data['teacher']->id,
        'session_key' => 'daily',
    ]);
    $this->assertDatabaseMissing('attendance_records', ['student_id' => $data['studentB']->id, 'session_key' => 'daily']);

    Livewire::actingAs($data['teacher'])->test(Attendance::class)
        ->set('schoolClassId', (string) $data['classB']->id)
        ->call('save')
        ->assertForbidden();
});

it('lets a subject teacher record attendance from mappings without a timetable slot', function () {
    $data = mappedTeacherFixture();
    $selection = 'assignment:'.$data['classB']->id.':'.$data['math']->id;

    Livewire::actingAs($data['teacher'])->test(SubjectAttendance::class)
        ->assertSee('Mathematics')
        ->assertSee('Class B')
        ->set('slotId', $selection)
        ->assertSee('Learner Class B')
        ->assertDontSee('Learner Class A')
        ->set('statuses.'.$data['studentB']->id, 'late')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendance_records', [
        'student_id' => $data['studentB']->id,
        'subject_id' => $data['math']->id,
        'school_class_id' => $data['classB']->id,
        'status' => 'late',
        'recorded_by' => $data['teacher']->id,
    ]);
});

it('shows the union of class-teacher and subject-teacher classes on the dashboard', function () {
    $data = mappedTeacherFixture();

    $this->actingAs($data['teacher'])->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('Teacher Dashboard')
        ->assertSee('Class teacher responsibility')
        ->assertSee('Class A')
        ->assertSee('Class B')
        ->assertSeeInOrder(['Assigned classes', '2'])
        ->assertSeeInOrder(['Learners', '2']);
});
