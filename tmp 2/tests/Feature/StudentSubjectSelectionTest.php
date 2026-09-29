<?php

use App\Livewire\MarksEntry;
use App\Livewire\StudentSubjectSelections;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Services\StudentSubjectSelectionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function subjectSelectionContext(string $stage = 'advanced_level', int $sortOrder = 5): array
{
    $school = School::create([
        'name' => 'Secondary Selection School',
        'slug' => 'selection-school-'.uniqid(),
        'school_type' => 'secondary',
    ]);
    $term = Term::create([
        'school_id' => $school->id,
        'name' => 'Term 1',
        'year' => 2026,
        'is_current' => true,
        'status' => 'open',
    ]);
    $class = SchoolClass::create([
        'school_id' => $school->id,
        'name' => 'Senior '.$sortOrder,
        'education_stage' => $stage,
        'sort_order' => $sortOrder,
        'is_system' => true,
    ]);
    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'employment_status' => 'active',
    ]);
    $student = Student::create([
        'school_id' => $school->id,
        'school_class_id' => $class->id,
        'term_id' => $term->id,
        'status' => 'active',
        'name' => 'Amina Learner',
        'admission_no' => 'S-'.$sortOrder.'-01',
    ]);
    $subjects = collect(['Mathematics', 'Physics', 'Chemistry', 'General Paper', 'History'])
        ->map(fn ($name, $index) => Subject::create([
            'school_id' => $school->id,
            'name' => $name,
            'code' => 'SUB'.($index + 1),
        ]));

    foreach ($subjects as $subject) {
        DB::table('class_subjects')->insert([
            'school_id' => $school->id,
            'term_id' => $term->id,
            'school_class_id' => $class->id,
            'subject_id' => $subject->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    return compact('school', 'term', 'class', 'admin', 'student', 'subjects');
}

it('saves an individual A-Level selection with principal and subsidiary subjects', function () {
    $data = subjectSelectionContext();

    Livewire::actingAs($data['admin'])
        ->test(StudentSubjectSelections::class)
        ->set('classId', (string) $data['class']->id)
        ->set('studentId', (string) $data['student']->id)
        ->set('selections.'.$data['subjects'][0]->id, 'principal')
        ->set('selections.'.$data['subjects'][1]->id, 'principal')
        ->set('selections.'.$data['subjects'][2]->id, 'principal')
        ->set('selections.'.$data['subjects'][3]->id, 'subsidiary')
        ->call('save')
        ->assertHasNoErrors()
        ->assertSee('subject selection was saved');

    expect(DB::table('student_subject_selections')
        ->where('term_id', $data['term']->id)
        ->where('student_id', $data['student']->id)
        ->count())->toBe(4)
        ->and(DB::table('student_subject_selections')
            ->where('student_id', $data['student']->id)
            ->where('selection_type', 'principal')
            ->count())->toBe(3);
});

it('supports core and elective selection for Senior 3 and Senior 4', function () {
    $data = subjectSelectionContext('lower_secondary', 3);

    Livewire::actingAs($data['admin'])
        ->test(StudentSubjectSelections::class)
        ->set('classId', (string) $data['class']->id)
        ->set('studentId', (string) $data['student']->id)
        ->set('selections.'.$data['subjects'][0]->id, 'core')
        ->set('selections.'.$data['subjects'][4]->id, 'elective')
        ->call('save')
        ->assertHasNoErrors();

    expect(DB::table('student_subject_selections')
        ->where('student_id', $data['student']->id)
        ->pluck('selection_type')
        ->sort()
        ->values()
        ->all())->toBe(['core', 'elective']);
});

it('excludes a learner from marks entry for a subject they did not select', function () {
    $data = subjectSelectionContext();
    $otherStudent = Student::create([
        'school_id' => $data['school']->id,
        'school_class_id' => $data['class']->id,
        'term_id' => $data['term']->id,
        'status' => 'active',
        'name' => 'Unconfigured Learner',
    ]);

    foreach ([0 => 'principal', 1 => 'principal', 2 => 'principal', 3 => 'subsidiary'] as $index => $type) {
        DB::table('student_subject_selections')->insert([
            'school_id' => $data['school']->id,
            'term_id' => $data['term']->id,
            'student_id' => $data['student']->id,
            'subject_id' => $data['subjects'][$index]->id,
            'selection_type' => $type,
            'selected_by' => $data['admin']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    $exam = Exam::create([
        'school_id' => $data['school']->id,
        'term_id' => $data['term']->id,
        'school_class_id' => $data['class']->id,
        'name' => 'Beginning of Term',
    ]);
    $paper = ExamPaper::create([
        'exam_id' => $exam->id,
        'subject_id' => $data['subjects'][4]->id,
        'maximum_score' => 100,
        'weighting' => 1,
    ]);

    Livewire::actingAs($data['admin'])
        ->test(MarksEntry::class)
        ->set('paperId', (string) $paper->id)
        ->assertDontSee($data['student']->name)
        ->assertSee($otherStudent->name);

    expect(StudentSubjectSelectionService::studentTakesSubject(
        $data['student'],
        $data['term']->id,
        $data['subjects'][4]->id,
    ))->toBeFalse();
});

it('shows subject selection under Academics in the administrator sidebar', function () {
    $data = subjectSelectionContext();

    $this->actingAs($data['admin'])
        ->get(route('subject-selections.index'))
        ->assertOk()
        ->assertSee('Individual Subject Selection')
        ->assertSee('Student Subject Selection')
        ->assertSee('href="'.route('subject-selections.index').'"', false);
});

it('hides subject selection from non-secondary school sidebars', function () {
    $school = School::create([
        'name' => 'Primary School',
        'slug' => 'primary-school-'.uniqid(),
        'school_type' => 'primary',
    ]);
    $admin = User::factory()->create([
        'school_id' => $school->id,
        'role' => 'admin',
        'employment_status' => 'active',
    ]);

    $this->actingAs($admin)
        ->get(route('subjects.index'))
        ->assertOk()
        ->assertDontSee('Student Subject Selection')
        ->assertDontSee('href="'.route('subject-selections.index').'"', false);
});
