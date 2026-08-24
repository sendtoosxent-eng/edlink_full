<?php

use App\Livewire\FeePayments;
use App\Models\Arrears;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\StudentFeeAdjustment;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function feeAdjustmentFixture(string $slug = 'adjustment-school'): array
{
    $school = School::create(['name' => 'Adjustment School', 'slug' => $slug, 'status' => 'active', 'is_demo' => false, 'license_status' => 'active']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary Seven']);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'name' => 'Learner One', 'status' => 'active']);
    StudentEnrolment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'base_fee_amount' => 800000, 'status' => 'active', 'enrolled_at' => now()->toDateString()]);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    return compact('school', 'term', 'class', 'category', 'student', 'admin');
}

it('keeps a requested adjustment out of the balance until an administrator approves it', function () {
    extract(feeAdjustmentFixture());

    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('openPaymentForm', $student->id)
        ->set('adjustmentType', 'negotiated')
        ->set('adjustmentCalculation', 'fixed')
        ->set('adjustmentValue', '200000')
        ->set('adjustmentReason', 'The family requested temporary financial assistance.')
        ->call('requestAdjustment')
        ->assertHasNoErrors()
        ->assertSee('Status: PENDING APPROVAL')
        ->assertSee('Fee Adjustment Approval Queue');

    $adjustment = StudentFeeAdjustment::firstOrFail();
    expect($adjustment->status)->toBe('pending')
        ->and($student->fresh()->totalDue($term))->toBe(800000.0);

    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('reviewAdjustment', $adjustment->id, 'approved')
        ->assertHasNoErrors();

    expect($student->fresh()->mappedFeeAmount($term))->toBe(800000.0)
        ->and($student->fresh()->feeAdjustmentTotal($term))->toBe(200000.0)
        ->and($student->fresh()->adjustedFeeAmount($term))->toBe(600000.0)
        ->and($student->fresh()->totalDue($term))->toBe(600000.0)
        ->and(AuditLog::where('event', 'finance.fee_adjustment.requested')->exists())->toBeTrue()
        ->and(AuditLog::where('event', 'finance.fee_adjustment.approved')->exists())->toBeTrue();

    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('cancelAdjustment', $adjustment->id)
        ->assertHasNoErrors();
    expect($student->fresh()->totalDue($term))->toBe(800000.0)
        ->and($adjustment->fresh()->status)->toBe('cancelled')
        ->and(AuditLog::where('event', 'finance.fee_adjustment.cancelled')->exists())->toBeTrue();
});

it('allows finance adjustment staff to open the screen, request, and approve an adjustment', function () {
    extract(feeAdjustmentFixture('finance-staff-adjustment-school'));
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Finance Adjustments Officer',
        'permissions' => ['finance.adjustments'],
    ]);
    $financeUser = User::factory()->create([
        'school_id' => $school->id,
        'designation_id' => $designation->id,
        'role' => 'bursar',
    ]);

    expect($financeUser->hasPermission('finance.adjustments'))->toBeTrue()
        ->and($financeUser->hasPermission('finance.payments'))->toBeFalse();
    $this->actingAs($financeUser)->get(route('fee-payments.index'))
        ->assertOk()
        ->assertSee('Fee Adjustment Approval Queue')
        ->assertSee('No fee adjustments are waiting for approval');

    Livewire::actingAs($financeUser)->test(FeePayments::class)
        ->call('openPaymentForm', $student->id)
        ->assertDontSee('Record payment & print receipt')
        ->set('adjustmentType', 'negotiated')
        ->set('adjustmentCalculation', 'fixed')
        ->set('adjustmentValue', '200000')
        ->set('adjustmentReason', 'Finance approved a lower individual fee for this learner.')
        ->call('requestAdjustment')
        ->assertHasNoErrors();

    $adjustment = StudentFeeAdjustment::firstOrFail();
    Livewire::actingAs($financeUser)->test(FeePayments::class)
        ->call('reviewAdjustment', $adjustment->id, 'approved')
        ->assertHasNoErrors();

    expect($adjustment->fresh()->status)->toBe('approved')
        ->and($adjustment->fresh()->reviewed_by)->toBe($financeUser->id)
        ->and($student->fresh()->totalDue($term))->toBe(600000.0);

    Livewire::actingAs($financeUser)->test(FeePayments::class)
        ->call('recordPayment')
        ->assertForbidden();
});

it('supports percentage discounts and final agreed fees', function () {
    extract(feeAdjustmentFixture('calculation-school'));

    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('openPaymentForm', $student->id)
        ->set('adjustmentType', 'scholarship')->set('adjustmentCalculation', 'percentage')
        ->set('adjustmentValue', '25')->set('adjustmentReason', 'Merit scholarship approved for this learner.')
        ->call('requestAdjustment')->assertHasNoErrors();

    expect((float) StudentFeeAdjustment::first()->amount)->toBe(200000.0);

    StudentFeeAdjustment::query()->delete();
    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('openPaymentForm', $student->id)
        ->set('adjustmentType', 'negotiated')->set('adjustmentCalculation', 'final_fee')
        ->set('adjustmentValue', '500000')->set('adjustmentReason', 'Management agreed on a final term fee with the family.')
        ->call('requestAdjustment')->assertHasNoErrors();

    expect((float) StudentFeeAdjustment::first()->amount)->toBe(300000.0);
});

it('prevents cross-school approval and excessive reductions', function () {
    extract(feeAdjustmentFixture('protected-school'));
    $adjustment = StudentFeeAdjustment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'requested_by' => $admin->id, 'type' => 'waiver', 'calculation_type' => 'fixed', 'value' => 900000, 'amount' => 900000, 'reason' => 'Invalid excessive request for testing.', 'status' => 'pending']);
    $otherSchool = School::create(['name' => 'Other School', 'slug' => 'other-adjustment-school']);
    $otherAdmin = User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'admin']);

    Livewire::actingAs($otherAdmin)->test(FeePayments::class)
        ->call('reviewAdjustment', $adjustment->id, 'approved');
    expect($adjustment->fresh()->status)->toBe('pending');

    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('openPaymentForm', $student->id)
        ->set('adjustmentCalculation', 'fixed')->set('adjustmentValue', '900000')
        ->set('adjustmentReason', 'This reduction exceeds the learner fee amount.')
        ->call('requestAdjustment')->assertHasErrors(['adjustmentValue']);
});

it('never approves combined adjustments above the initial term fee', function () {
    extract(feeAdjustmentFixture('combined-adjustment-cap-school'));
    $first = StudentFeeAdjustment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'requested_by' => $admin->id, 'type' => 'negotiated', 'calculation_type' => 'fixed', 'value' => 500000, 'amount' => 500000, 'reason' => 'First valid reduction for this learner.', 'status' => 'pending']);
    $second = StudentFeeAdjustment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'requested_by' => $admin->id, 'type' => 'scholarship', 'calculation_type' => 'fixed', 'value' => 400000, 'amount' => 400000, 'reason' => 'Second reduction that would exceed the initial fee.', 'status' => 'pending']);

    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('reviewAdjustment', $first->id, 'approved')
        ->assertHasNoErrors();
    Livewire::actingAs($admin)->test(FeePayments::class)
        ->call('reviewAdjustment', $second->id, 'approved')
        ->assertHasNoErrors();

    expect($first->fresh()->status)->toBe('approved')
        ->and($second->fresh()->status)->toBe('pending')
        ->and($student->fresh()->feeAdjustmentTotal($term))->toBe(500000.0)
        ->and($student->fresh()->totalDue($term))->toBe(300000.0);
});

it('uses approved adjusted balances when rolling arrears', function () {
    extract(feeAdjustmentFixture('arrears-adjustment-school'));
    StudentFeeAdjustment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'requested_by' => $admin->id, 'reviewed_by' => $admin->id, 'type' => 'negotiated', 'calculation_type' => 'fixed', 'value' => 200000, 'amount' => 200000, 'reason' => 'Approved negotiated reduction.', 'status' => 'approved', 'reviewed_at' => now()]);

    $term->closeTerm(true);

    expect((float) Arrears::where('student_id', $student->id)->value('amount'))->toBe(600000.0);
});
