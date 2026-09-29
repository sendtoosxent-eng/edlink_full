<?php

use App\Models\Designation;
use App\Models\PlatformAdmin;
use App\Models\PlatformAuditLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\StudentCategory;
use App\Models\Term;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(RefreshDatabase::class);

function schoolImportPlatformAdmin(): PlatformAdmin
{
    return PlatformAdmin::create(['name'=>'Import Owner','email'=>'imports@edlink.test','password'=>'password','role'=>'platform_owner','is_active'=>true]);
}

function importPlatformSession($test, PlatformAdmin $admin)
{
    return $test->actingAs($admin, 'platform')->withSession(['platform_mfa_passed'=>true,'platform_last_activity'=>now()->timestamp]);
}

it('imports students into the selected school with guardians and enrolments', function () {
    $admin = schoolImportPlatformAdmin();
    $school = School::create(['name'=>'Import School','slug'=>'import-school','status'=>'active','is_demo'=>false,'license_status'=>'active','license_student_limit'=>100]);
    SchoolClass::create(['school_id'=>$school->id,'name'=>'Primary 5']);
    StudentCategory::create(['school_id'=>$school->id,'name'=>'Day']);
    Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open']);
    $csv = "name,admission_no,class,stream,category,date_of_birth,gender,admission_date,guardian_name,guardian_relationship,guardian_phone,guardian_email\nJane Doe,ADM-1001,Primary 5,,Day,2014-03-12,female,2026-02-01,Mary Doe,Mother,0700000000,mary@example.com\n";

    importPlatformSession($this, $admin)
        ->post(route('platform.schools.imports.students', $school), ['file'=>UploadedFile::fake()->createWithContent('students.csv', $csv)])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('students', ['school_id'=>$school->id,'name'=>'Jane Doe','admission_no'=>'ADM-1001']);
    $this->assertDatabaseHas('student_guardians', ['name'=>'Mary Doe','email'=>'mary@example.com']);
    $this->assertDatabaseHas('student_enrolments', ['school_id'=>$school->id,'status'=>'active']);
    expect(PlatformAuditLog::where('event','platform.students.imported')->exists())->toBeTrue();
});

it('imports teachers with their school designation and rejects duplicates', function () {
    $admin = schoolImportPlatformAdmin();
    $school = School::create(['name'=>'Teacher Import School','slug'=>'teacher-import-school','status'=>'active','is_demo'=>false,'license_status'=>'active']);
    $designation = Designation::create(['school_id'=>$school->id,'name'=>'Subject Teacher','permissions'=>['academics.subjects']]);
    $headers = 'name,email,phone,job_title,designation,temporary_password,joined_at,base_salary,employment_status';
    $row = 'John Teacher,john@example.com,0700000001,Mathematics Teacher,Subject Teacher,ChangeMe123!,2026-02-01,500000,active';

    importPlatformSession($this, $admin)
        ->post(route('platform.schools.imports.teachers', $school), ['file'=>UploadedFile::fake()->createWithContent('teachers.csv', "$headers\n$row\n")])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('users', ['school_id'=>$school->id,'designation_id'=>$designation->id,'email'=>'john@example.com','role'=>'teacher']);
    importPlatformSession($this, $admin)
        ->post(route('platform.schools.imports.teachers', $school), ['file'=>UploadedFile::fake()->createWithContent('teachers.csv', "$headers\n$row\n")])
        ->assertSessionHasErrors();
    expect($school->users()->where('email','john@example.com')->count())->toBe(1);
});

it('rejects a student file atomically when any row references missing school data', function () {
    $admin = schoolImportPlatformAdmin();
    $school = School::create(['name'=>'Atomic Import School','slug'=>'atomic-import-school','status'=>'active','is_demo'=>false,'license_status'=>'active']);
    SchoolClass::create(['school_id'=>$school->id,'name'=>'Primary 5']);
    StudentCategory::create(['school_id'=>$school->id,'name'=>'Day']);
    Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open']);
    $headers = 'name,admission_no,class,stream,category,date_of_birth,gender,admission_date,guardian_name,guardian_relationship,guardian_phone,guardian_email';
    $rows = "Good Student,ADM-1,Primary 5,,Day,,male,2026-02-01,Good Parent,Parent,,\nBad Student,ADM-2,Missing Class,,Day,,female,2026-02-01,Bad Parent,Parent,,";

    importPlatformSession($this, $admin)
        ->post(route('platform.schools.imports.students', $school), ['file'=>UploadedFile::fake()->createWithContent('students.csv', "$headers\n$rows\n")])
        ->assertSessionHasErrors();

    expect($school->students()->count())->toBe(0);
});