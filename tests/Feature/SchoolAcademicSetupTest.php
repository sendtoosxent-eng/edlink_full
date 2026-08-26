<?php

use App\Models\GradingScale;
use App\Models\School;
use App\Services\SchoolAcademicSetup;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('provisions and protects the standard primary structure', function () {
    $school = School::create(['name' => 'Primary Demo', 'school_type' => 'primary', 'slug' => 'primary-demo']);

    SchoolAcademicSetup::provision($school);

    expect($school->classes()->count())->toBe(7)
        ->and($school->classes()->where('is_system', true)->count())->toBe(7)
        ->and($school->classes()->where('education_stage', 'primary')->count())->toBe(7)
        ->and(GradingScale::where('school_id', $school->id)->where('education_stage', 'primary')->orderByDesc('minimum_percentage')->pluck('grade')->all())
        ->toBe(['D1', 'D2', 'C3', 'C4', 'C5', 'C6', 'P7', 'P8', 'F9']);
});

it('separates lower and advanced secondary structures', function () {
    $school = School::create(['name' => 'Secondary Demo', 'school_type' => 'secondary', 'slug' => 'secondary-demo']);

    SchoolAcademicSetup::provision($school);

    expect($school->classes()->where('education_stage', 'lower_secondary')->count())->toBe(4)
        ->and($school->classes()->where('education_stage', 'advanced_level')->count())->toBe(2)
        ->and(GradingScale::where('school_id', $school->id)->where('education_stage', 'advanced_level')->orderByDesc('minimum_percentage')->pluck('grade')->all())
        ->toBe(['A', 'B', 'C', 'D', 'E', 'O', 'F']);
});

it('provisions a useful default kindergarten structure', function () {
    $school = School::create(['name' => 'Kindergarten Demo', 'school_type' => 'kindergarten', 'slug' => 'kindergarten-demo']);

    SchoolAcademicSetup::provision($school);

    expect($school->classes()->pluck('name')->all())->toBe(['Baby Class', 'Middle Class', 'Top Class'])
        ->and(GradingScale::where('school_id', $school->id)->where('education_stage', 'kindergarten')->count())->toBeGreaterThan(0);
});

it('provisions certificate and diploma cohorts for vocational institutes', function () {
    $school = School::create(['name' => 'Vocational Demo', 'school_type' => 'tertiary', 'slug' => 'vocational-demo']);

    SchoolAcademicSetup::provision($school);

    expect($school->classes()->pluck('name')->all())->toBe([
        'Certificate Year 1', 'Certificate Year 2', 'Diploma Year 1', 'Diploma Year 2',
    ])->and(GradingScale::where('school_id', $school->id)->where('education_stage', 'tertiary')->count())->toBeGreaterThan(0);
});
