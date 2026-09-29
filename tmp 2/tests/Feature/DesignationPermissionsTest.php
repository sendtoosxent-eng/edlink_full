<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('uses granular designation rights instead of the account type', function () {
    $school = School::create(['name' => 'Permissions School', 'slug' => 'permissions-school']);
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Subject Teacher',
        'permissions' => ['attendance.subject', 'exams.marks'],
    ]);
    $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher', 'designation_id' => $designation->id]);

    expect($teacher->hasModuleAccess('attendance'))->toBeTrue()
        ->and($teacher->hasPermission('attendance.subject'))->toBeTrue()
        ->and($teacher->hasPermission('attendance.daily'))->toBeFalse()
        ->and($teacher->hasPermission('exams.marks'))->toBeTrue()
        ->and($teacher->hasPermission('finance.payments'))->toBeFalse();
});

it('keeps administrators unrestricted and supports legacy module designations', function () {
    $school = School::create(['name' => 'Legacy Permissions School', 'slug' => 'legacy-permissions-school']);
    $legacy = Designation::create(['school_id' => $school->id, 'name' => 'Legacy Finance', 'permissions' => ['finance']]);
    $bursar = User::factory()->create(['school_id' => $school->id, 'role' => 'bursar', 'designation_id' => $legacy->id]);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    expect($bursar->hasPermission('finance.payments'))->toBeTrue()
        ->and($bursar->hasPermission('finance.expenses'))->toBeTrue()
        ->and($bursar->hasPermission('staff.payroll'))->toBeFalse()
        ->and($admin->hasPermission('settings.manage'))->toBeTrue();
});