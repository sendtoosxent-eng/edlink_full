<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

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