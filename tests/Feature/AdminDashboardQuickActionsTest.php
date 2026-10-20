<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows ten administrator quick actions and no dark-mode control', function () {
    $school = School::create([
        'name' => 'Quick Actions School',
        'slug' => 'quick-actions-school',
        'status' => 'active',
        'is_demo' => false,
        'license_status' => 'active',
    ]);
    $administrator = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'employment_status' => 'active',
    ]);

    $response = $this->actingAs($administrator)->get(route('dashboard'));

    $response->assertOk()
        ->assertSee('Quick actions')
        ->assertSee('Register Student')
        ->assertSee('Record Fee Payment')
        ->assertSee('Register Staff')
        ->assertSee('Mark Attendance')
        ->assertSee('Create Announcement')
        ->assertSee('View Debtors')
        ->assertSee('Create Expense')
        ->assertSee('Student Profile')
        ->assertSee('data-quick-action="student-profile"', false)
        ->assertDontSee('Manage Current Term')
        ->assertSee('View Reports')
        ->assertSee('School Settings')
        ->assertDontSee('$store.theme', false)
        ->assertDontSee('html.dark', false);

    expect(substr_count($response->getContent(), 'data-quick-action='))->toBe(10);
});
