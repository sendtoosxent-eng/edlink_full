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
    $this->get(route('graduates.recommendation', $record))->assertOk()->assertHeader('content-type', 'application/pdf');
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

it('saves structured graduate external results and includes them in recommendations', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);
    $record = app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 74.5);
    $math = \App\Models\Subject::create(['school_id' => $data['school']->id, 'name' => 'Mathematics', 'code' => 'MAT']);
    $english = \App\Models\Subject::create(['school_id' => $data['school']->id, 'name' => 'English Language', 'code' => 'ENG']);
    $component = Livewire::test(\App\Livewire\Graduates::class)->call('beginExternalResult', $record->id)
        ->set('externalResultType', 'UACE')
        ->set('subjectResults', [
            ['subject_id' => (string) $math->id, 'mark' => '85', 'grade' => 'A', 'aggregate' => '6'],
            ['subject_id' => (string) $english->id, 'mark' => '', 'grade' => 'B', 'aggregate' => '5'],
        ])->set('resultSummary', ['exam_year' => '2026', 'index_number' => 'U1234/001', 'total_aggregate' => '11', 'total_points' => '17', 'division' => 'Division 1', 'overall_result' => 'Passed with distinction', 'remarks' => 'Eligible for further study.'])
        ->call('saveExternalResult')->assertHasNoErrors()->assertSet('resultId', null);
    $record->refresh();
    expect(strlen($record->external_result))->toBeGreaterThan(255)
        ->and($record->externalResults()['subjects'][0]['subject'])->toBe('Mathematics')
        ->and($record->externalResults()['subjects'][1]['mark'])->toBeNull()
        ->and($record->external_result_entered_by)->toBe($data['user']->id)
        ->and((float) $record->final_average)->toBe(74.5);
    $component->call('beginExternalResult', $record->id)->assertSet('subjectResults.0.mark', '85')->assertSet('resultSummary.total_points', '17');
    $html = view('students.graduate-recommendation', ['record' => $record->load('school', 'student', 'schoolClass'), 'settings' => [], 'badgeDataUri' => null])->render();
    foreach (['UACE results', 'Mathematics', 'English Language', 'U1234/001', 'Total aggregate', 'Total points', 'Division 1', 'Passed with distinction', 'Eligible for further study.', 'School final average'] as $text) expect($html)->toContain($text);
    $this->get(route('graduates.recommendation', $record))->assertOk()->assertHeader('content-type', 'application/pdf');
    $this->assertDatabaseHas('audit_logs', ['event' => 'graduate.external_results.saved']);
});

it('validates graduate result subjects grades and totals before saving', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);
    $record = app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 70);
    $subject = \App\Models\Subject::create(['school_id' => $data['school']->id, 'name' => 'Physics', 'code' => 'PHY']);
    $other = School::create(['name' => 'Foreign Results School', 'slug' => 'foreign-results-school']);
    $foreignSubject = \App\Models\Subject::create(['school_id' => $other->id, 'name' => 'Foreign Subject', 'code' => 'FOR']);
    $component = Livewire::test(\App\Livewire\Graduates::class)->call('beginExternalResult', $record->id);
    $component->set('subjectResults', [['subject_id' => $foreignSubject->id, 'mark' => '70', 'grade' => '', 'aggregate' => '']])->call('saveExternalResult')->assertHasErrors(['subjectResults.0.subject_id']);
    $component->set('subjectResults', [['subject_id' => $subject->id, 'mark' => '101', 'grade' => '', 'aggregate' => '']])->call('saveExternalResult')->assertHasErrors(['subjectResults.0.mark']);
    $component->set('subjectResults', [['subject_id' => $subject->id, 'mark' => '', 'grade' => '', 'aggregate' => '']])->call('saveExternalResult')->assertHasErrors(['subjectResults.0.grade']);
    $component->set('subjectResults', array_fill(0, 2, ['subject_id' => $subject->id, 'mark' => '0', 'grade' => '', 'aggregate' => '']))->call('saveExternalResult')->assertHasErrors(['subjectResults.0.subject_id']);
    expect($record->fresh()->external_result)->toBeNull();
    $component->set('subjectResults', [['subject_id' => $subject->id, 'mark' => '0', 'grade' => '', 'aggregate' => '0']])->set('resultSummary.total_aggregate', '0')->call('saveExternalResult')->assertHasNoErrors();
    expect($record->fresh()->externalResults()['subjects'][0]['mark'])->toBe('0');
});

it('retains older graduate subject grades when opening the new results form', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);
    $record = app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 70);
    $subject = \App\Models\Subject::create(['school_id' => $data['school']->id, 'name' => 'Biology', 'code' => 'BIO']);
    $record->update(['external_result_type' => 'UCE', 'external_result' => json_encode(['Biology' => 'D1'])]);
    Livewire::test(\App\Livewire\Graduates::class)->call('beginExternalResult', $record->id)->assertSet('subjectResults.0.subject_id', (string) $subject->id)->assertSet('subjectResults.0.grade', 'D1')->assertSet('externalResultType', 'UCE');
    expect($record->externalResults()['subjects'][0]['grade'])->toBe('D1');
});

it('restricts graduate results editing to authorized staff in the same school', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);
    $record = app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 70);
    $teacher = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);
    Livewire::actingAs($teacher)->test(\App\Livewire\Graduates::class)->call('beginExternalResult', $record->id)->assertForbidden();
    $other = School::create(['name' => 'Other Graduate School', 'slug' => 'other-graduate-school']);
    $admin = User::factory()->create(['school_id' => $other->id, 'role' => 'admin']);
    Livewire::actingAs($admin)->test(\App\Livewire\Graduates::class)->call('beginExternalResult', $record->id)->assertNotFound();
});

it('embeds the saved school badge and student photo in the recommendation', function () {
    $data = graduationFixture();
    $this->actingAs($data['user']);
    $record = app(GraduationService::class)->graduate($data['enrolment'], $data['term'], 74.5);
    \Illuminate\Support\Facades\Storage::fake('public');
    $badge = \Illuminate\Http\UploadedFile::fake()->image('badge.png', 80, 80);
    $photo = \Illuminate\Http\UploadedFile::fake()->image('student.jpg', 90, 120);
    $data['school']->update(['badge_path' => $badge->store('schools', 'public'), 'address' => 'School Road, Kampala', 'phone' => '+256700123456', 'email' => 'office@completion.test', 'website' => 'https://completion.test', 'motto' => 'Learning for life']);
    $data['student']->update(['photo_path' => $photo->store('students', 'public')]);
    $pdf = \Mockery::mock(\Barryvdh\DomPDF\PDF::class);
    $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
    $pdf->shouldReceive('download')->once()->andReturn(response('PDF', 200, ['Content-Type' => 'application/pdf']));
    \Barryvdh\DomPDF\Facade\Pdf::shouldReceive('loadView')->once()->withArgs(function ($view, $payload) {
        expect($view)->toBe('students.graduate-recommendation');
        expect($payload['badgeDataUri'])->toStartWith('data:image/png;base64,');
        expect($payload['studentPhotoDataUri'])->toStartWith('data:image/jpeg;base64,');
        $html = view($view, $payload)->render();
        foreach (['School Road, Kampala', '+256700123456', 'office@completion.test', 'https://completion.test', 'Learning for life', $payload['badgeDataUri'], $payload['studentPhotoDataUri']] as $value) expect($html)->toContain($value);
        return true;
    })->andReturn($pdf);
    $this->get(route('graduates.recommendation', $record))->assertOk()->assertHeader('content-type', 'application/pdf');
});
