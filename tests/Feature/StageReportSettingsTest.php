<?php

use App\Models\School;
use App\Models\SchoolSetting;
use App\Services\StageReportSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('loads only the saved settings for the requested education stage', function () {
    $school = School::create(['name' => 'Reporting School', 'school_type' => 'secondary', 'slug' => 'reporting-school']);

    SchoolSetting::create(['school_id' => $school->id, 'key' => 'report_lower_secondary_pass_mark', 'value' => '45']);
    SchoolSetting::create(['school_id' => $school->id, 'key' => 'report_lower_secondary_best_subjects', 'value' => '8']);
    SchoolSetting::create(['school_id' => $school->id, 'key' => 'report_lower_secondary_show_fees', 'value' => 'disabled']);
    SchoolSetting::create(['school_id' => $school->id, 'key' => 'report_lower_secondary_next_term_starts', 'value' => '2026-09-14']);
    SchoolSetting::create(['school_id' => $school->id, 'key' => 'report_advanced_level_pass_mark', 'value' => '60']);

    $lower = StageReportSettings::get($school->id, 'lower_secondary');
    $advanced = StageReportSettings::get($school->id, 'advanced_level');

    expect($lower['pass'])->toBe(45.0)
        ->and($lower['best'])->toBe(8)
        ->and($lower['show_fees'])->toBeFalse()
        ->and($lower['next_term_starts'])->toBe('2026-09-14')
        ->and($advanced['pass'])->toBe(60.0)
        ->and($advanced['show_fees'])->toBeTrue();
});
