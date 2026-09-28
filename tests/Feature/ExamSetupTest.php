<?php

use App\Livewire\ExamSetup;
use App\Models\Exam;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('limits exam papers to subjects offered to the exam class and term', function () {
    $school = School::create(['name' => 'Exam School', 'school_type' => 'primary', 'slug' => 'exam-school']);
    $user = User::factory()->create(['school_id' => $school->id]);
    DB::table('user_permissions')->insert(['user_id' => $user->id, 'permission' => 'exams.setup']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2026-04-01', 'is_current' => true]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 4']);
    $offered = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics']);
    $notOffered = Subject::create(['school_id' => $school->id, 'name' => 'Physics']);
    $exam = Exam::create(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => 'Midterm']);
    DB::table('class_subjects')->insert(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'subject_id' => $offered->id]);

    Livewire::actingAs($user)->test(ExamSetup::class)
        ->set('examId', (string) $exam->id)
        ->assertSee('Mathematics')
        ->assertDontSee('Physics')
        ->set('subjectId', (string) $notOffered->id)
        ->call('addPaper')
        ->assertHasErrors('subjectId');

    expect(DB::table('exam_papers')->count())->toBe(0);
});

it('does not allow scoring rules to change after marks are entered', function () {
    $school = School::create(['name' => 'Exam School', 'school_type' => 'primary', 'slug' => 'exam-school']);
    $user = User::factory()->create(['school_id' => $school->id]);
    DB::table('user_permissions')->insert(['user_id' => $user->id, 'permission' => 'exams.setup']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => 2026, 'start_date' => '2026-01-01', 'end_date' => '2026-04-01', 'is_current' => true]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary 4']);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics']);
    $exam = Exam::create(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'name' => 'Midterm']);
    DB::table('class_subjects')->insert(['school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id, 'subject_id' => $subject->id]);
    $paperId = DB::table('exam_papers')->insertGetId(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100, 'weighting' => 1]);
    $studentId = DB::table('students')->insertGetId(['school_id' => $school->id, 'school_class_id' => $class->id, 'name' => 'Learner', 'admission_no' => 'EX-1', 'status' => 'active', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('exam_marks')->insert(['exam_paper_id' => $paperId, 'student_id' => $studentId, 'score' => 80, 'created_at' => now(), 'updated_at' => now()]);

    Livewire::actingAs($user)->test(ExamSetup::class)
        ->set('examId', (string) $exam->id)
        ->set('subjectId', (string) $subject->id)
        ->set('maximumScore', '80')
        ->call('addPaper')
        ->assertHasErrors('subjectId');

    expect((float) DB::table('exam_papers')->where('id', $paperId)->value('maximum_score'))->toBe(100.0);
});
