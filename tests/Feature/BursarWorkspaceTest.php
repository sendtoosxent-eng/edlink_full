<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bursarWorkspaceUser(): User
{
    $school = School::create(['name' => 'Bursar Workspace School', 'slug' => 'bursar-workspace-school']);
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Bursar',
        'permissions' => DesignationPermissions::defaults()['Bursar'],
    ]);

    return User::factory()->create([
        'school_id' => $school->id,
        'designation_id' => $designation->id,
        'role' => 'bursar',
        'employment_status' => 'active',
    ]);
}

it('redirects a bursar away from the administrator dashboard', function () {
    $bursar = bursarWorkspaceUser();

    $this->actingAs($bursar)->get(route('dashboard'))->assertRedirect(route('workbench.home'));
});

it('shows the bursar accounting dashboard instead of the generic staff dashboard', function () {
    $bursar = bursarWorkspaceUser();

    $this->actingAs($bursar)->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('Finance Dashboard')
        ->assertSee('Income and expenditure')
        ->assertSee('Expected fees')
        ->assertDontSee('Upcoming events');
});

it('shows a bursar only finance-based report choices', function () {
    $bursar = bursarWorkspaceUser();

    $this->actingAs($bursar)->get(route('reports.index'))
        ->assertOk()
        ->assertSee('Fee demand')
        ->assertSee('Fee collection')
        ->assertSee('Cash pool statement')
        ->assertSee('Expenses')
        ->assertDontSee('Student register')
        ->assertDontSee('Daily attendance')
        ->assertDontSee('Subject performance');
});