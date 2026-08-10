<?php

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Services\AdmissionNumberGenerator;
use App\Services\StaffNumberGenerator;

it('builds school-scoped student identifiers from the saved student id', function () {
    $school = (new School)->forceFill(['id' => 12]);
    $student = (new Student)->forceFill(['id' => 345]);

    expect((new AdmissionNumberGenerator)->generateForStudent($school, $student))
        ->toBe('STU-12-000345');
});

it('builds school-scoped staff identifiers from the saved user id', function () {
    $school = (new School)->forceFill(['id' => 12]);
    $staff = (new User)->forceFill(['id' => 6789]);

    expect((new StaffNumberGenerator)->generate($school, $staff))
        ->toBe('STF-12-006789');
});
