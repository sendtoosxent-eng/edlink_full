<?php

use App\Livewire\Homework;
use App\Models\HomeworkAssignment;
use App\Models\HomeworkSubmission;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function homeworkContext(): array
{
    $school=School::create(['name'=>'Homework School','slug'=>'homework-school-'.uniqid()]);
    $term=Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open']);
    $class=SchoolClass::create(['school_id'=>$school->id,'name'=>'Primary 6']);
    $subject=Subject::create(['school_id'=>$school->id,'name'=>'Mathematics','code'=>'MATH']);
    $teacher=User::factory()->create(['school_id'=>$school->id,'role'=>'teacher','employment_status'=>'active']);
    DB::table('staff_subjects')->insert(['school_id'=>$school->id,'term_id'=>$term->id,'user_id'=>$teacher->id,'subject_id'=>$subject->id,'school_class_id'=>$class->id,'created_at'=>now(),'updated_at'=>now()]);
    $student=Student::create(['school_id'=>$school->id,'school_class_id'=>$class->id,'term_id'=>$term->id,'status'=>'active','name'=>'Amina Learner','admission_no'=>'P6-01']);
    $studentUser=User::factory()->create(['school_id'=>$school->id,'role'=>'student']);
    $studentUser->portalStudents()->attach($student->id,['school_id'=>$school->id,'relationship'=>'student']);
    return compact('school','term','class','subject','teacher','student','studentUser');
}

it('lets an assigned teacher publish homework', function () {
    $data=homeworkContext();
    Livewire::actingAs($data['teacher'])->test(Homework::class)
        ->set('classId',(string)$data['class']->id)
        ->set('subjectId',(string)$data['subject']->id)
        ->set('title','Fractions practice')
        ->set('instructions','Complete questions one to ten.')
        ->set('dueAt',now()->addDay()->format('Y-m-d\TH:i'))
        ->set('maximumScore','20')
        ->call('createAssignment')
        ->assertHasNoErrors()
        ->assertSee('Homework published to the class.');

    expect(HomeworkAssignment::where([
        'teacher_id'=>$data['teacher']->id,
        'school_class_id'=>$data['class']->id,
        'subject_id'=>$data['subject']->id,
        'title'=>'Fractions practice',
    ])->exists())->toBeTrue();
});

it('lets the linked student submit and the teacher review homework', function () {
    $data=homeworkContext();
    $assignment=HomeworkAssignment::create([
        'school_id'=>$data['school']->id,'term_id'=>$data['term']->id,'teacher_id'=>$data['teacher']->id,
        'school_class_id'=>$data['class']->id,'subject_id'=>$data['subject']->id,'title'=>'Essay',
        'instructions'=>'Write one page.','maximum_score'=>30,'due_at'=>now()->addDay(),'published_at'=>now(),
    ]);

    Livewire::actingAs($data['studentUser'])->test(Homework::class)
        ->assertSee('Essay')
        ->call('selectAssignment',$assignment->id)
        ->set('answer','My completed answer.')
        ->call('submitHomework')
        ->assertHasNoErrors()
        ->assertSee('Your homework was submitted.');

    $submission=HomeworkSubmission::first();
    expect($submission->student_id)->toBe($data['student']->id)
        ->and($submission->status)->toBe('submitted');

    Livewire::actingAs($data['teacher'])->test(Homework::class)
        ->call('selectAssignment',$assignment->id)
        ->set('reviewScores.'.$submission->id,'24')
        ->set('reviewFeedback.'.$submission->id,'Good work.')
        ->call('review',$submission->id)
        ->assertHasNoErrors();

    expect($submission->fresh()->status)->toBe('reviewed')
        ->and((float)$submission->fresh()->score)->toBe(24.0)
        ->and($submission->fresh()->feedback)->toBe('Good work.');
});

it('does not show homework from another class to a student', function () {
    $data=homeworkContext();
    $otherClass=SchoolClass::create(['school_id'=>$data['school']->id,'name'=>'Primary 7']);
    HomeworkAssignment::create([
        'school_id'=>$data['school']->id,'term_id'=>$data['term']->id,'teacher_id'=>$data['teacher']->id,
        'school_class_id'=>$otherClass->id,'subject_id'=>$data['subject']->id,'title'=>'Private class work',
        'instructions'=>'Not for Primary 6.','maximum_score'=>10,'due_at'=>now()->addDay(),'published_at'=>now(),
    ]);
    Livewire::actingAs($data['studentUser'])->test(Homework::class)->assertDontSee('Private class work');
});

it('lets a school administrator access homework from the shared sidebar', function () {
    $data=homeworkContext();
    $admin=User::factory()->create(['school_id'=>$data['school']->id,'role'=>'admin','employment_status'=>'active']);

    $this->actingAs($admin)->get(route('homework.index'))
        ->assertOk()
        ->assertSee('Homework')
        ->assertSee('Create New Homework')
        ->assertSee('href="'.route('homework.index').'"', false);
});

it('shows homework as a top-level link on the school admin dashboard', function () {
    $data=homeworkContext();
    $admin=User::factory()->create(['school_id'=>$data['school']->id,'role'=>'admin','employment_status'=>'active']);

    $this->actingAs($admin)->get(route('dashboard'))
        ->assertOk()
        ->assertSee('href="'.route('homework.index').'"', false)
        ->assertSee('Homework');
});
