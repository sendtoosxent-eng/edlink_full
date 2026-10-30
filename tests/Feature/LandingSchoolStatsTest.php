<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('counts registered schools without counting demonstration schools', function () {
    DB::table('schools')->insert([
        ['name' => 'Registered School', 'slug' => 'registered-school', 'is_demo' => false],
        ['name' => 'Demo School', 'slug' => 'demo-school', 'is_demo' => true],
    ]);

    $this->get(route('home'))->assertOk()
        ->assertViewHas('registeredSchoolCount', 1)
        ->assertSee('Registered schools')
        ->assertSee('School-focused');
});
