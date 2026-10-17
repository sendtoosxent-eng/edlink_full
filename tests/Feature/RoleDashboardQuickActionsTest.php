<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use App\Support\DesignationPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows permission-safe quick actions on teacher and bursar dashboards', function () {
    $school = School::create(['name' => 'Role Actions School', 'slug' => 'role-actions-school']);
    $teacherDesignation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Subject Teacher',
        'permissions' => DesignationPermissions::defaults()['Subject Teacher'],
    ]);
    $bursarDesignation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Bursar',
        'permissions' => DesignationPermissions::defaults()['Bursar'],
    ]);
    $teacher = User::factory()->create(['school_id' => $school->id, 'designation_id' => $teacherDesignation->id, 'role' => 'teacher', 'employment_status' => 'active']);
    $bursar = User::factory()->create(['school_id' => $school->id, 'designation_id' => $bursarDesignation->id, 'role' => 'bursar', 'employment_status' => 'active']);

    $teacherResponse = $this->actingAs($teacher)->get(route('workbench.home'));
    $teacherResponse->assertOk()
        ->assertSee('Quick actions')
        ->assertSee('Teacher tools')
        ->assertSee('data-quick-action="homework"', false)
        ->assertSee('data-quick-action="subject-attendance"', false)
        ->assertSee('data-quick-action="enter-marks"', false)
        ->assertSee('data-quick-action="timetable"', false)
        ->assertDontSee('data-quick-action="record-payment"', false);
    expect(substr_count($teacherResponse->getContent(), 'data-quick-action='))->toBeLessThanOrEqual(10);

    $bursarResponse = $this->actingAs($bursar)->get(route('workbench.home'));
    $bursarResponse->assertOk()
        ->assertSee('Quick actions')
        ->assertSee('Bursar tools')
        ->assertSee('data-quick-action="record-payment"', false)
        ->assertSee('data-quick-action="record-expense"', false)
        ->assertSee('data-quick-action="reconcile-accounts"', false)
        ->assertSee('data-quick-action="run-payroll"', false)
        ->assertDontSee('data-quick-action="enter-marks"', false);
    expect(substr_count($bursarResponse->getContent(), 'data-quick-action='))->toBeLessThanOrEqual(10);
});

it('links a custom staff role only to pages allowed by its exact permissions', function () {
    $school = School::create(['name' => 'Custom Role School', 'slug' => 'custom-role-school']);
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Expense Clerk',
        'permissions' => ['finance.expenses'],
    ]);
    $staff = User::factory()->create([
        'school_id' => $school->id,
        'designation_id' => $designation->id,
        'role' => 'registrar',
        'employment_status' => 'active',
    ]);

    $this->actingAs($staff)->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('data-quick-action="expenses"', false)
        ->assertSee(route('expenses.index'), false)
        ->assertDontSee('data-quick-action="fee-payments"', false)
        ->assertDontSee(route('fee-payments.index'), false);
});

it('shows distinct quick actions for parent and student portals', function () {
    $school = School::create(['name' => 'Portal Actions School', 'slug' => 'portal-actions-school']);
    $student = Student::create([
        'school_id' => $school->id,
        'name' => 'Demo Learner',
        'admission_no' => 'PA-001',
        'status' => 'active',
    ]);
    $parent = User::factory()->create(['school_id' => $school->id, 'role' => 'parent', 'employment_status' => 'active']);
    $studentUser = User::factory()->create(['school_id' => $school->id, 'role' => 'student', 'employment_status' => 'active']);
    $parent->portalStudents()->attach($student->id, ['school_id' => $school->id, 'relationship' => 'Guardian']);
    $studentUser->portalStudents()->attach($student->id, ['school_id' => $school->id, 'relationship' => 'Student']);

    $parentResponse = $this->actingAs($parent)->get(route('portal.home'));
    $parentResponse->assertOk()
        ->assertSee('Parent tools')
        ->assertSee('data-quick-action="learner-results"', false)
        ->assertSee('data-quick-action="fees-payments"', false)
        ->assertSee('data-quick-action="notifications"', false)
        ->assertDontSee('data-quick-action="my-homework"', false);
    expect(substr_count($parentResponse->getContent(), 'data-quick-action='))->toBeLessThanOrEqual(10);

    $studentResponse = $this->actingAs($studentUser)->get(route('portal.home'));
    $studentResponse->assertOk()
        ->assertSee('Student tools')
        ->assertSee('data-quick-action="my-homework"', false)
        ->assertSee('data-quick-action="my-results"', false)
        ->assertSee('data-quick-action="my-attendance"', false)
        ->assertDontSee('data-quick-action="fees-payments"', false);
    expect(substr_count($studentResponse->getContent(), 'data-quick-action='))->toBeLessThanOrEqual(10);
});
