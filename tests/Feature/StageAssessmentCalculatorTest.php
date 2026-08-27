<?php

use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Services\StageAssessmentCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('combines approved examinations using configured stage weights', function () {
    $school = School::create(['name' => 'Weighted School', 'slug' => 'weighted-school']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => 2026]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Senior One']);
    $student = Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'Learner']);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics']);

    foreach ([['Mid Term', 60], ['End Term', 80]] as [$name, $score]) {
        $exam = Exam::create(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => $name]);
        $paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100, 'weighting' => 1]);
        DB::table('exam_marks')->insert(['exam_paper_id' => $paper->id, 'student_id' => $student->id, 'score' => $score, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $paper->id, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
    }

    $marks = StageAssessmentCalculator::marks($student, $term, [
        'calculation_method' => 'weighted', 'missing_assessment_rule' => 'incomplete',
        'assessment_weights' => [['name' => 'Mid Term', 'weight' => 40], ['name' => 'End Term', 'weight' => 60]],
    ]);

    expect($marks)->toHaveCount(1)
        ->and($marks->first()['percentage'])->toBe(72.0)
        ->and($marks->first()['score'])->toBe(72.0)
        ->and($marks->first()['maximum'])->toBe(100.0)
        ->and($marks->first()['incomplete'])->toBeFalse();
});
