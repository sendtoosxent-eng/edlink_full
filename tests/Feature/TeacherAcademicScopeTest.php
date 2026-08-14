<?php

use App\Livewire\MarksEntry;
use App\Models\Designation;
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
use Livewire\Livewire;

uses(RefreshDatabase::class);

function scopedTeacherFixture(): array
{
    $school=School::create(['name'=>'Scoped School','slug'=>'scoped-school']);
    $term=Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open','locked'=>false]);
    $classA=SchoolClass::create(['school_id'=>$school->id,'name'=>'P5','education_stage'=>'primary','sort_order'=>1]);
    $classB=SchoolClass::create(['school_id'=>$school->id,'name'=>'P6','education_stage'=>'primary','sort_order'=>2]);
    $classDesignation=Designation::create(['school_id'=>$school->id,'name'=>'Class Teacher','permissions'=>['exams.marks','exams.results']]);
    $subjectDesignation=Designation::create(['school_id'=>$school->id,'name'=>'Subject Teacher','permissions'=>['exams.marks','exams.results']]);
    $classTeacher=User::factory()->create(['school_id'=>$school->id,'designation_id'=>$classDesignation->id,'role'=>'teacher']);
    $subjectTeacher=User::factory()->create(['school_id'=>$school->id,'designation_id'=>$subjectDesignation->id,'role'=>'teacher']);
    $classA->update(['class_teacher_user_id'=>$classTeacher->id]);
    $studentA=Student::create(['school_id'=>$school->id,'school_class_id'=>$classA->id,'name'=>'Learner A','admission_no'=>'A-1','status'=>'active']);
    $studentB=Student::create(['school_id'=>$school->id,'school_class_id'=>$classB->id,'name'=>'Learner B','admission_no'=>'B-1','status'=>'active']);
    $math=Subject::create(['school_id'=>$school->id,'name'=>'Mathematics','code'=>'MATH']);
    $english=Subject::create(['school_id'=>$school->id,'name'=>'English','code'=>'ENG']);
    $exam=Exam::create(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$classA->id,'name'=>'Midterm']);
    $mathPaper=ExamPaper::create(['exam_id'=>$exam->id,'subject_id'=>$math->id,'maximum_score'=>100,'weighting'=>1]);
    $englishPaper=ExamPaper::create(['exam_id'=>$exam->id,'subject_id'=>$english->id,'maximum_score'=>100,'weighting'=>1]);
    DB::table('staff_subjects')->insert([
        ['school_id'=>$school->id,'term_id'=>$term->id,'user_id'=>$classTeacher->id,'subject_id'=>$math->id,'school_class_id'=>$classA->id,'created_at'=>now(),'updated_at'=>now()],
        ['school_id'=>$school->id,'term_id'=>$term->id,'user_id'=>$subjectTeacher->id,'subject_id'=>$math->id,'school_class_id'=>$classA->id,'created_at'=>now(),'updated_at'=>now()],
    ]);
    return compact('school','term','classA','classB','classTeacher','subjectTeacher','studentA','studentB','mathPaper','englishPaper');
}

it('gives a class teacher a read-only directory containing only their class', function () {
    $data=scopedTeacherFixture();
    $this->actingAs($data['classTeacher'])->get(route('students.index'))
        ->assertOk()->assertSee('Learner A')->assertDontSee('Learner B')->assertSee('View only')->assertDontSee('Register Student');
    $this->actingAs($data['classTeacher'])->get(route('students.register'))->assertForbidden();
});

it('does not give a subject teacher the general student directory', function () {
    $data=scopedTeacherFixture();
    $this->actingAs($data['subjectTeacher'])->get(route('students.index'))->assertForbidden();
    $this->actingAs($data['subjectTeacher'])->get(route('students.register'))->assertForbidden();
});

it('limits a class teacher marks entry to subjects explicitly assigned in their class', function () {
    $data=scopedTeacherFixture();
    Livewire::actingAs($data['classTeacher'])->test(MarksEntry::class)
        ->assertSee('Mathematics')->assertDontSee('English');
});

it('limits a subject teacher to their assigned subject', function () {
    $data=scopedTeacherFixture();
    Livewire::actingAs($data['subjectTeacher'])->test(MarksEntry::class)
        ->assertSee('Mathematics')->assertDontSee('English')
        ->set('paperId',(string)$data['englishPaper']->id)->assertForbidden();
});
