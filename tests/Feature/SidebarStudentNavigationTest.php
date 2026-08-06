<?php

use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('opens student navigation on the dashboard and includes categories and graduates', function () {
    $school = School::create([
        'name' => 'Navigation School',
        'slug' => 'navigation-school',
        'status' => 'active',
        'is_demo' => false,
        'license_status' => 'active',
    ]);
    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'employment_status' => 'active',
    ]);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee("open: 'students'", false)
        ->assertSee('href="'.route('student-categories.index').'"', false)
        ->assertSee('href="'.route('graduates.index').'"', false);
});
