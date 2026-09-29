<?php

use App\Livewire\Reports;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('materializes only the current database page for the student register', function () {
    $school = School::create(['name' => 'Pagination School', 'slug' => 'pagination-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    foreach (range(1, 45) as $number) {
        Student::create(['school_id' => $school->id, 'name' => sprintf('Learner %02d', $number), 'status' => 'active']);
    }

    $component = Livewire::actingAs($admin)->test(Reports::class);
    $first = $component->viewData('result');
    expect($first['rows'])->toHaveCount(40)
        ->and($first['pagination']['total'])->toBe(45)
        ->and($first['pagination']['last_page'])->toBe(2);

    $component->call('nextPage');
    $second = $component->viewData('result');
    expect($second['rows'])->toHaveCount(5)
        ->and($second['pagination']['from'])->toBe(41)
        ->and($second['pagination']['to'])->toBe(45);
});
