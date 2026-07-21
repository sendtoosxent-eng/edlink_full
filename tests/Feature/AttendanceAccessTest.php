<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function attendanceUser(string $role): User
{
    $school = School::create(['name' => fake()->company(), 'slug' => fake()->unique()->slug(), 'status' => 'active', 'is_demo' => false]);
    $designation = Designation::create(['school_id' => $school->id, 'name' => 'Attendance '.fake()->unique()->word(), 'permissions' => ['attendance']]);

    return User::factory()->create(['school_id' => $school->id, 'designation_id' => $designation->id, 'role' => $role]);
}

test('teachers can open subject attendance but not administrator attendance screens', function () {
    $teacher = attendanceUser('teacher');

    $this->actingAs($teacher)->get(route('attendance.subject'))->assertOk();
    $this->actingAs($teacher)->get(route('attendance.index'))->assertForbidden();
    $this->actingAs($teacher)->get(route('attendance.reports'))->assertForbidden();
});

test('administrators can open daily attendance and reports but not the teacher screen', function () {
    $admin = attendanceUser('admin');

    $this->actingAs($admin)->get(route('attendance.index'))->assertOk();
    $this->actingAs($admin)->get(route('attendance.reports'))->assertOk();
    $this->actingAs($admin)->get(route('attendance.subject'))->assertForbidden();
});
