<?php

use App\Models\Designation;
use App\Models\User;

test('administrators retain unrestricted module access', function () {
    $user = new User(['role' => 'admin']);

    expect($user->hasModuleAccess('attendance'))->toBeTrue()
        ->and($user->hasModuleAccess('settings'))->toBeTrue();
});

test('staff without a designation have no module access', function () {
    $user = new User(['role' => 'teacher']);

    expect($user->hasModuleAccess('attendance'))->toBeFalse();
});

test('designation permissions grant only selected modules', function () {
    $user = new User(['role' => 'teacher', 'designation_id' => 10]);
    $user->setRelation('designation', new Designation(['permissions' => ['attendance']]));

    expect($user->hasModuleAccess('attendance'))->toBeTrue()
        ->and($user->hasModuleAccess('finance'))->toBeFalse();
});

test('student and parent portal accounts are not restricted by staff designations', function (string $role) {
    $user = new User(['role' => $role]);

    expect($user->hasModuleAccess('exams'))->toBeTrue();
})->with(['student', 'parent']);
