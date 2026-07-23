<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function teacherWorkspaceUser(array $extraPermissions = []): User
{
    $school = School::create(['name' => 'Teacher Workspace School', 'slug' => 'teacher-workspace-school-'.uniqid()]);
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Subject Teacher',
        'permissions' => array_values(array_unique([...DesignationPermissions::defaults()['Subject Teacher'], ...$extraPermissions])),
    ]);

    return User::factory()->create([
        'school_id' => $school->id,
        'designation_id' => $designation->id,
        'role' => 'teacher',
        'employment_status' => 'active',
    ]);
}

it('shows a teacher-focused dashboard with teaching cards and charts', function () {
    $teacher = teacherWorkspaceUser();

    $this->actingAs($teacher)->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('Teacher Dashboard')
        ->assertSee('Assigned subjects')
        ->assertSee('Assigned classes')
        ->assertSee('Lessons today')
        ->assertSee('Pending mark sheets')
        ->assertSee('Attendance activity')
        ->assertSee('Assigned-subject performance')
        ->assertDontSee('Finance Dashboard');
});

it('shows extra sidebar modules when a teacher designation receives extra rights', function () {
    $teacher = teacherWorkspaceUser(['finance.expenses']);

    $this->actingAs($teacher)->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('Teacher Dashboard')
        ->assertSee('Finance')
        ->assertSee('Expenses');
});