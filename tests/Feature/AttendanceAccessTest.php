<?php

use App\Livewire\Attendance;
use App\Models\Designation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function attendanceUser(string $role, array $permissions): User
{
    $school = School::create(['name' => fake()->company(), 'slug' => fake()->unique()->slug(), 'status' => 'active', 'is_demo' => false]);
    $designation = Designation::create(['school_id' => $school->id, 'name' => 'Attendance '.fake()->unique()->word(), 'permissions' => $permissions]);

    return User::factory()->create(['school_id' => $school->id, 'designation_id' => $designation->id, 'role' => $role]);
}

test('a subject teacher opens only the attendance screen granted by the designation', function () {
    $teacher = attendanceUser('teacher', ['attendance.subject']);

    $this->actingAs($teacher)->get(route('attendance.subject'))->assertOk();
    $this->actingAs($teacher)->get(route('attendance.index'))->assertForbidden();
    $this->actingAs($teacher)->get(route('attendance.reports'))->assertForbidden();
});

test('a DOS opens daily attendance and reports but not ungranted subject attendance', function () {
    $dos = attendanceUser('academic_admin', ['attendance.daily', 'attendance.reports']);

    $this->actingAs($dos)->get(route('attendance.index'))->assertOk();
    $this->actingAs($dos)->get(route('attendance.reports'))->assertOk();
    $this->actingAs($dos)->get(route('attendance.subject'))->assertForbidden();
});

test('administrators remain unrestricted across attendance screens', function () {
    $admin = attendanceUser('admin', []);

    $this->actingAs($admin)->get(route('attendance.index'))->assertOk();
    $this->actingAs($admin)->get(route('attendance.reports'))->assertOk();
    $this->actingAs($admin)->get(route('attendance.subject'))->assertOk();
});

test('daily attendance can be saved again without creating a duplicate record', function () {
    $school = School::create(['name' => 'Repeat Attendance School', 'slug' => 'repeat-attendance-school', 'status' => 'active', 'is_demo' => false]);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term One', 'term_number' => 1, 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary One']);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'name' => 'Attendance Learner', 'status' => 'active']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    $component = Livewire::actingAs($admin)->test(Attendance::class)
        ->set('attendanceDate', '2026-08-27')
        ->set("statuses.{$student->id}", 'present')
        ->call('save')->assertHasNoErrors();

    $component->set("statuses.{$student->id}", 'absent')->call('save')->assertHasNoErrors();

    expect(\App\Models\AttendanceRecord::where('student_id', $student->id)->where('session_key', 'daily')->count())->toBe(1)
        ->and(\App\Models\AttendanceRecord::where('student_id', $student->id)->value('status'))->toBe('absent');
});
