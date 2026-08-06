<?php

use App\Models\GraduationRecord;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\Term;
use App\Models\User;
use App\Services\GraduationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function graduationFixture(int $termNumber = 3): array
{
    $school = School::create(['name' => 'Completion School', 'slug' => 'completion-school', 'status' => 'active', 'is_demo' => false]);
    $user = User::factory()->create(['school_id' => $school->id, 'role' => 'superadmin']);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Senior 6', 'sort_order' => 6]);
    $category = StudentCategory::create(['school_id' => $school->id, 'name' => 'Day']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term '.$termNumber, 'term_number' => $termNumber, 'year' => 2026, 'status' => 'closed', 'is_current' => false, 'closed_at' => now()]);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'term_id' => $term->id, 'name' => 'Final Learner', 'admission_no' => 'FIN-001', 'status' => 'active']);
    $enrolment = StudentEnrolment::create(['school_id' => $school->id, 'student_id' => $student->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'student_category_id' => $category->id, 'base_fee_amount' => 500000, 'status' => 'active', 'enrolled_at' => now()->toDateString()]);

    return compact('school', 'user', 'class', 'term', 'student', 'enrolment');
}

it('enforces the three-term academic sequence', function () {
    $school = School::create(['name' => 'Sequence School', 'slug' => 'sequence-school']);
    $one = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'term_number' => 1, 'year' => 2026]);
    $two = Term::create(['school_id' => $school->id, 'name' => 'Term 2', 'term_number' => 2, 'year' => 2026]);
    $three = Term::create(['school_id' => $school->id, 'name' => 'Term 3', 'term_number' => 3, 'year' => 2026]);
    $next = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'term_number' => 1, 'year' => 2027]);

    expect($one->canProgressTo($two))->toBeTrue()
        ->and($two->canProgressTo($three))->toBeTrue()
        ->and($three->canProgressTo($next))->toBeTrue()
        ->and($one->canProgressTo($three))->toBeFalse()
        ->and($two->canProgressTo($next))->toBeFalse();
});

it('allows graduation only after term three and preserves a permanent record', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);

    $record = app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 74.5);

    expect($record)->toBeInstanceOf(GraduationRecord::class)
        ->and($record->portal_access)->toBe('read_only')
        ->and((float) $record->outstanding_balance)->toBe(500000.0)
        ->and($data['student']->fresh()->status)->toBe('graduated')
        ->and($data['enrolment']->fresh()->status)->toBe('graduated')
        ->and($data['enrolment']->fresh()->exited_at)->not->toBeNull();

    $this->get(route('graduates.index'))->assertOk();
    $this->get(route('graduates.certificate', $record))->assertOk()->assertHeader('content-type', 'application/pdf');
});

it('rejects graduation before term three', function () {
    $data = graduationFixture(2);
    $this->actingAs($data['user']);
    app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 80);
})->throws(ValidationException::class);

it('reverses an accidental graduation with an audit-safe retained record', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);
    $service = app(GraduationService::class);
    $record = $service->graduate($data['enrolment'], $data['term'], 70);
    $service->reverse($record, 'Entered in error');

    expect($record->fresh()->reversed_at)->not->toBeNull()
        ->and($record->fresh()->reversal_reason)->toBe('Entered in error')
        ->and($data['student']->fresh()->status)->toBe('active')
        ->and($data['enrolment']->fresh()->status)->toBe('active')
        ->and($data['enrolment']->fresh()->promotion_outcome)->toBeNull();
});

it('continues learners in the same class between terms one and two', function () {
    $data = graduationFixture(1);
    $target = Term::create(['school_id' => $data['school']->id, 'name' => 'Term 2', 'term_number' => 2, 'year' => 2026, 'status' => 'pending', 'is_current' => false]);
    $this->actingAs($data['user']);

    Livewire::test(\App\Livewire\PromotionsV2::class)
        ->set('sourceTermId', (string) $data['term']->id)
        ->set('targetTermId', (string) $target->id)
        ->call('generatePreview')
        ->assertSet('previewReady', true)
        ->assertSet('preview.0.outcome', 'continued')
        ->assertSet('preview.0.target_class_id', $data['class']->id);
});
