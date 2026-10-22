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

test('attendance summary reconciles gender totals and keeps same named classes separate', function () {
    $admin = attendanceUser('admin', []);
    $school = $admin->school;
    $term = Term::create(['school_id' => $school->id, 'name' => 'Current term', 'term_number' => 1, 'year' => now()->year, 'is_current' => true, 'status' => 'open']);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    StudentCategory::create(['school_id' => $school->id, 'name' => 'Boarder']);
    StudentCategory::create(['school_id' => $school->id, 'name' => 'Weekend programme']);
    $otherSchool = School::create(['name' => 'Other school', 'slug' => 'other-category-school', 'status' => 'active']);
    StudentCategory::create(['school_id' => $otherSchool->id, 'name' => 'Other school category']);
    $classes = collect([1, 2])->map(fn () => SchoolClass::create(['school_id' => $school->id, 'name' => 'Same class name']));
    foreach ([['male', 'present', 0], ['female', 'late', 0], [null, 'excused', 0], ['female', 'absent', 1]] as [$gender, $status, $index]) {
        $student = Student::create(['school_id' => $school->id, 'school_class_id' => $classes[$index]->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'name' => 'Learner '.$status, 'gender' => $gender, 'status' => 'active']);
        \App\Models\AttendanceRecord::create(['school_id' => $school->id, 'term_id' => $term->id, 'student_id' => $student->id, 'school_class_id' => $classes[$index]->id, 'attendance_date' => today(), 'session_key' => 'daily', 'status' => $status, 'recorded_by' => $admin->id]);
    }
    Livewire::actingAs($admin)->test(\App\Livewire\AttendanceReport::class)
        ->assertSee('Grand total')
        ->assertDontSee('Other school category')
        ->assertViewHas('categorySummary', function ($summary) {
            expect($summary)->toHaveCount(3);
            expect($summary->sum('total'))->toBe(4);
            expect($summary->firstWhere('class', 'Day')['total'])->toBe(4);
            expect($summary->firstWhere('class', 'Boarder')['total'])->toBe(0);
            expect($summary->firstWhere('class', 'Weekend programme')['total'])->toBe(0);
            return true;
        })
        ->assertViewHas('attendanceSummary', function ($summary) {
            expect($summary)->toHaveCount(2);
            expect($summary->sum('total'))->toBe(4);
            expect($summary->sum('unspecified'))->toBe(1);
            expect($summary->sum(fn ($row) => $row['groups']['male']['present']))->toBe(1);
            expect($summary->sum(fn ($row) => $row['groups']['female']['late']))->toBe(1);
            expect($summary->sum(fn ($row) => $row['groups']['female']['absent']))->toBe(1);
            return true;
        })
        ->set('schoolClassId', (string) $classes[1]->id)
        ->assertViewHas('attendanceSummary', fn ($summary) => $summary->count() === 1 && $summary->sum('total') === 1)
        ->assertViewHas('categorySummary', fn ($summary) => $summary->sum('total') === 1)
        ->call('exportExcel')
        ->assertFileDownloaded();
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
