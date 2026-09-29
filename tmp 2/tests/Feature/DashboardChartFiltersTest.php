<?php

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('provides working year and class datasets to the administrator line charts', function () {
    $school = School::create([
        'name' => 'Chart School',
        'slug' => 'chart-school-'.uniqid(),
        'school_type' => 'secondary',
    ]);
    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'employment_status' => 'active',
    ]);
    $term = Term::create([
        'school_id' => $school->id,
        'name' => 'Term 1',
        'year' => 2026,
        'is_current' => true,
        'status' => 'open',
    ]);
    $seniorOne = SchoolClass::create([
        'school_id' => $school->id,
        'name' => 'Senior 1',
        'education_stage' => 'lower_secondary',
        'sort_order' => 1,
    ]);
    $seniorTwo = SchoolClass::create([
        'school_id' => $school->id,
        'name' => 'Senior 2',
        'education_stage' => 'lower_secondary',
        'sort_order' => 2,
    ]);
    $studentOne = Student::create([
        'school_id' => $school->id,
        'school_class_id' => $seniorOne->id,
        'term_id' => $term->id,
        'status' => 'active',
        'name' => 'Senior One Learner',
    ]);
    $studentTwo = Student::create([
        'school_id' => $school->id,
        'school_class_id' => $seniorTwo->id,
        'term_id' => $term->id,
        'status' => 'active',
        'name' => 'Senior Two Learner',
    ]);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics']);

    DB::table('fee_payments')->insert([
        [
            'school_id' => $school->id, 'student_id' => $studentOne->id, 'term_id' => $term->id,
            'amount' => 150000, 'method' => 'cash', 'paid_at' => '2026-01-15',
            'created_at' => now(), 'updated_at' => now(),
        ],
        [
            'school_id' => $school->id, 'student_id' => $studentOne->id, 'term_id' => $term->id,
            'amount' => 90000, 'method' => 'cash', 'paid_at' => '2025-02-10',
            'created_at' => now(), 'updated_at' => now(),
        ],
    ]);

    foreach ([[$seniorOne, $studentOne, 80], [$seniorTwo, $studentTwo, 60]] as [$class, $student, $score]) {
        $exam = Exam::create([
            'school_id' => $school->id,
            'term_id' => $term->id,
            'school_class_id' => $class->id,
            'name' => 'Mid Term',
        ]);
        $paper = ExamPaper::create([
            'exam_id' => $exam->id,
            'subject_id' => $subject->id,
            'maximum_score' => 100,
            'weighting' => 1,
        ]);
        DB::table('exam_paper_submissions')->insert([
            'exam_paper_id' => $paper->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('exam_marks')->insert([
            'exam_paper_id' => $paper->id,
            'student_id' => $student->id,
            'score' => $score,
            'entered_by' => $admin->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $response = $this->actingAs($admin)->get(route('dashboard'));
    $response->assertOk()
        ->assertSee("paymentYearFilter.addEventListener('change'", false)
        ->assertSee("performanceClassFilter.addEventListener('change'", false);

    $paymentSeries = $response->viewData('paymentTrendByYear');
    $performanceSeries = $response->viewData('performanceSeries');

    expect((float) $paymentSeries['2026'][0])->toBe(150000.0)
        ->and((float) $paymentSeries['2025'][1])->toBe(90000.0)
        ->and($performanceSeries[(string) $seniorOne->id]['data']->all())->toBe([80.0])
        ->and($performanceSeries[(string) $seniorTwo->id]['data']->all())->toBe([60.0])
        ->and($performanceSeries['all']['data']->all())->toBe([70.0]);
});
