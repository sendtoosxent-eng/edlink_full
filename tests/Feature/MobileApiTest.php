<?php

use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamPaper;
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
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function mobileFixture(): array
{
    $school=School::create(['name'=>'Edlink Mobile School','slug'=>'mobile-school','school_number'=>'EDL-MOB01','license_status'=>'active','license_expires_at'=>now()->addYear()]);
    $other=School::create(['name'=>'Other School','slug'=>'other-school','school_number'=>'EDL-OTHER','license_status'=>'active','license_expires_at'=>now()->addYear()]);
    $term=Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open','locked'=>false]);
    $class=SchoolClass::create(['school_id'=>$school->id,'name'=>'S2','education_stage'=>'secondary','sort_order'=>1]);
    $otherClass=SchoolClass::create(['school_id'=>$other->id,'name'=>'S2','education_stage'=>'secondary','sort_order'=>1]);
    $designation=Designation::create(['school_id'=>$school->id,'name'=>'Subject Teacher','permissions'=>['attendance.subject','exams.marks']]);
    $teacher=User::factory()->create(['school_id'=>$school->id,'designation_id'=>$designation->id,'role'=>'teacher','email'=>'teacher@mobile.test']);
    $parent=User::factory()->create(['school_id'=>$school->id,'role'=>'parent']);
    $studentUser=User::factory()->create(['school_id'=>$school->id,'role'=>'student']);
    $student=Student::create(['school_id'=>$school->id,'school_class_id'=>$class->id,'name'=>'Linked Learner','admission_no'=>'M-1','status'=>'active']);
    $unlinked=Student::create(['school_id'=>$school->id,'school_class_id'=>$class->id,'name'=>'Unlinked Learner','admission_no'=>'M-2','status'=>'active']);
    $foreign=Student::create(['school_id'=>$other->id,'school_class_id'=>$otherClass->id,'name'=>'Foreign Learner','admission_no'=>'O-1','status'=>'active']);
    foreach([$parent,$studentUser] as $user) DB::table('portal_user_students')->insert(['school_id'=>$school->id,'user_id'=>$user->id,'student_id'=>$student->id,'relationship'=>$user->role,'created_at'=>now(),'updated_at'=>now()]);
    $subject=Subject::create(['school_id'=>$school->id,'name'=>'Mathematics','code'=>'MAT']);
    DB::table('staff_subjects')->insert(['school_id'=>$school->id,'term_id'=>$term->id,'user_id'=>$teacher->id,'subject_id'=>$subject->id,'school_class_id'=>$class->id,'created_at'=>now(),'updated_at'=>now()]);
    $exam=Exam::create(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$class->id,'name'=>'Midterm']);
    $paper=ExamPaper::create(['exam_id'=>$exam->id,'subject_id'=>$subject->id,'maximum_score'=>100,'weighting'=>1]);
    return compact('school','term','class','teacher','parent','studentUser','student','unlinked','foreign','subject','paper');
}

it('issues and revokes a device-specific sanctum token', function () {
    $data=mobileFixture();
    $response=$this->postJson('/api/v1/auth/login',['school_number'=>'edl-mob01','email'=>'teacher@mobile.test','password'=>'password','device_name'=>'Pixel 8']);
    $token=$response->assertOk()->assertJsonPath('data.user.role','teacher')->json('data.token');
    $this->withToken($token)->postJson('/api/v1/auth/logout')->assertOk();
    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('does not allow a parent to select an unlinked or cross-school learner', function () {
    $data=mobileFixture(); Sanctum::actingAs($data['parent'],['mobile']);
    $this->getJson('/api/v1/results?student_id='.$data['student']->id)->assertOk();
    $this->getJson('/api/v1/results?student_id='.$data['unlinked']->id)->assertNotFound();
    $this->getJson('/api/v1/results?student_id='.$data['foreign']->id)->assertNotFound();
});

it('rejects hostile learner identifiers in teacher attendance writes', function () {
    $data=mobileFixture(); Sanctum::actingAs($data['teacher'],['mobile']);
    $payload=['school_class_id'=>$data['class']->id,'subject_id'=>$data['subject']->id,'attendance_date'=>today()->toDateString(),'session_key'=>'math-1','records'=>[['student_id'=>$data['foreign']->id,'status'=>'present']]];
    $this->postJson('/api/v1/attendance',$payload)->assertForbidden();
});

it('gives a class teacher a daily register and permits saving only that class', function () {
    $data=mobileFixture();
    $data['class']->update(['class_teacher_user_id'=>$data['teacher']->id]);
    Sanctum::actingAs($data['teacher'],['mobile']);

    $this->getJson('/api/v1/teaching-assignments')->assertOk()
        ->assertJsonFragment(['school_class_id'=>$data['class']->id,'subject_id'=>null,'attendance_type'=>'daily']);

    $payload=['school_class_id'=>$data['class']->id,'subject_id'=>null,'attendance_date'=>today()->toDateString(),'session_key'=>'daily','records'=>[['student_id'=>$data['student']->id,'status'=>'present']]];
    $this->postJson('/api/v1/attendance',$payload)->assertOk()->assertJsonPath('data.saved',1);
    $this->assertDatabaseHas('attendance_records',['student_id'=>$data['student']->id,'session_key'=>'daily','recorded_by'=>$data['teacher']->id]);
});

it('protects mobile payment information by learner linkage', function () {
    $data=mobileFixture(); Sanctum::actingAs($data['parent'],['mobile']);
    $this->getJson('/api/v1/payments?student_id='.$data['student']->id)->assertOk()
        ->assertJsonPath('data.student.id',$data['student']->id)
        ->assertJsonStructure(['data'=>['summary'=>['due','paid','balance'],'payments']]);
    $this->getJson('/api/v1/payments?student_id='.$data['unlinked']->id)->assertNotFound();
    $this->getJson('/api/v1/payments?student_id='.$data['foreign']->id)->assertNotFound();
});

it('allows teachers to request their own leave and blocks portal roles', function () {
    $data=mobileFixture(); $payload=['type'=>'Sick leave','starts_on'=>today()->addDay()->toDateString(),'ends_on'=>today()->addDays(2)->toDateString(),'reason'=>'Medical appointment'];
    Sanctum::actingAs($data['teacher'],['mobile']);
    $this->postJson('/api/v1/leave-requests',$payload)->assertOk()->assertJsonPath('data.status','pending');
    $this->assertDatabaseHas('staff_leaves',['school_id'=>$data['school']->id,'user_id'=>$data['teacher']->id,'status'=>'pending']);

    Sanctum::actingAs($data['parent'],['mobile']);
    $this->postJson('/api/v1/leave-requests',$payload)->assertForbidden();
});

it('rejects marks outside the assigned paper and maximum score', function () {
    $data=mobileFixture(); Sanctum::actingAs($data['teacher'],['mobile']);
    $this->putJson('/api/v1/exam-papers/'.$data['paper']->id.'/marks',['marks'=>[['student_id'=>$data['student']->id,'score'=>101]]])->assertUnprocessable();
    $this->putJson('/api/v1/exam-papers/'.$data['paper']->id.'/marks',['marks'=>[['student_id'=>$data['foreign']->id,'score'=>60]]])->assertForbidden();
});

it('shows portal users only their own homework submission and protects teacher review', function () {
    $data=mobileFixture();
    $assignment=HomeworkAssignment::create(['school_id'=>$data['school']->id,'term_id'=>$data['term']->id,'teacher_id'=>$data['teacher']->id,'school_class_id'=>$data['class']->id,'subject_id'=>$data['subject']->id,'title'=>'Private work','instructions'=>'Answer privately.','maximum_score'=>20,'due_at'=>now()->addDay(),'published_at'=>now()]);
    $own=HomeworkSubmission::create(['homework_assignment_id'=>$assignment->id,'student_id'=>$data['student']->id,'submitted_by'=>$data['studentUser']->id,'answer'=>'My private answer','submitted_at'=>now(),'status'=>'submitted']);
    HomeworkSubmission::create(['homework_assignment_id'=>$assignment->id,'student_id'=>$data['unlinked']->id,'submitted_by'=>$data['studentUser']->id,'answer'=>'Another private answer','submitted_at'=>now(),'status'=>'submitted']);

    Sanctum::actingAs($data['studentUser'],['mobile']);
    $this->getJson("/api/v1/homework/{$assignment->id}")->assertOk()
        ->assertJsonCount(1,'data.submissions')->assertJsonPath('data.submissions.0.id',$own->id)
        ->assertJsonMissing(['answer'=>'Another private answer']);
    $this->postJson("/api/v1/homework/{$assignment->id}/submissions/{$own->id}/review",['score'=>10])->assertForbidden();

    Sanctum::actingAs($data['teacher'],['mobile']);
    $this->postJson("/api/v1/homework/{$assignment->id}/submissions/{$own->id}/review",['score'=>21])->assertUnprocessable();
    $this->postJson("/api/v1/homework/{$assignment->id}/submissions/{$own->id}/review",['score'=>18,'feedback'=>'Well done'])->assertOk();
});
