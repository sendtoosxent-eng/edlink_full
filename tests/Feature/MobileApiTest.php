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
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Notification;
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

it('looks up an active school before asking for account details', function () {
    $data=mobileFixture();
    $this->postJson('/api/v1/auth/school',['school_number'=>'edl-mob01'])
        ->assertOk()->assertJsonPath('data.number','EDL-MOB01')->assertJsonPath('data.name',$data['school']->name);
    $this->postJson('/api/v1/auth/school',['school_number'=>'EDL-MISSING'])->assertUnprocessable();
});

it('requires and verifies a six digit otp for a real school', function () {
    Notification::fake(); Config::set('app.otp_force',true); $data=mobileFixture();
    $login=$this->postJson('/api/v1/auth/login',['school_number'=>$data['school']->school_number,'email'=>$data['teacher']->email,'password'=>'password','device_name'=>'Pixel 8','expected_role'=>'teacher'])
        ->assertOk()->assertJsonPath('data.otp_required',true)->assertJsonMissingPath('data.token');
    $challenge=$login->json('data.challenge_token'); $code=$data['teacher']->fresh()->getRawOriginal('otp_code');
    $this->postJson('/api/v1/auth/otp/verify',['challenge_token'=>$challenge,'code'=>'000000','device_name'=>'Pixel 8'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/otp/verify',['challenge_token'=>$challenge,'code'=>$code,'device_name'=>'Pixel 8'])
        ->assertOk()->assertJsonPath('data.otp_required',false)->assertJsonPath('data.user.role','teacher')->assertJsonStructure(['data'=>['token']]);
    expect($data['teacher']->fresh()->getRawOriginal('otp_code'))->toBeNull();
});

it('keeps mobile password recovery school scoped and non revealing', function () {
    Notification::fake(); $data=mobileFixture();
    $this->postJson('/api/v1/auth/password/forgot',['school_number'=>$data['school']->school_number,'email'=>$data['teacher']->email])
        ->assertOk()->assertJsonPath('data.message','If those details match an Edlink account, a password reset link has been sent by email.');
    $this->postJson('/api/v1/auth/password/forgot',['school_number'=>'EDL-NONE','email'=>'missing@example.test'])
        ->assertOk()->assertJsonPath('data.message','If those details match an Edlink account, a password reset link has been sent by email.');
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

it('looks up only an active school account and returns just its display identity', function () {
    $data = mobileFixture();
    $payload = ['school_number' => strtolower($data['school']->school_number), 'email' => strtoupper($data['teacher']->email), 'expected_role' => 'teacher'];
    $this->postJson('/api/v1/auth/account', $payload)->assertOk()
        ->assertExactJson(['data' => ['name' => $data['teacher']->name, 'avatar_url' => null], 'meta' => []]);
    expect($data['teacher']->tokens()->count())->toBe(0);
    $this->postJson('/api/v1/auth/account', [...$payload, 'expected_role' => 'parent'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/account', [...$payload, 'school_number' => 'EDL-OTHER'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/account', [...$payload, 'email' => 'missing@example.test'])->assertUnprocessable();
    $data['teacher']->update(['employment_status' => 'inactive']);
    $this->postJson('/api/v1/auth/account', $payload)->assertUnprocessable();
    $data['teacher']->forceFill(['employment_status' => 'active', 'email_verified_at' => null])->save();
    $this->postJson('/api/v1/auth/account', $payload)->assertUnprocessable();
    $data['teacher']->forceFill(['email_verified_at' => now()])->save();
    $data['school']->update(['license_status' => 'expired', 'license_expires_at' => now()->subDay()]);
    $this->postJson('/api/v1/auth/account', $payload)->assertUnprocessable();
});

it('varies the mobile workspace by actual class responsibility', function () {
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $workspace = $this->getJson('/api/v1/dashboard')->assertOk()->json('data.teacher_workspace');
    expect($workspace['role_label'])->toBe('Subject teacher');
    expect(collect($workspace['tools'])->pluck('id')->all())->toContain('attendance.subject', 'exams.marks')->not->toContain('attendance.index', 'students.index');
    $data['class']->update(['class_teacher_user_id' => $data['teacher']->id]);
    $workspace = $this->getJson('/api/v1/dashboard')->assertOk()->json('data.teacher_workspace');
    expect($workspace['role_label'])->toBe('Class teacher');
    expect(collect($workspace['tools'])->pluck('id')->all())->toContain('attendance.index', 'students.index', 'attendance.subject');
    expect($workspace['class_teacher_classes'][0]['id'])->toBe($data['class']->id);
});

it('rejects incomplete marks submissions and protects approved sheets', function () {
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->postJson('/api/v1/exam-papers/'.$data['paper']->id.'/submit')->assertUnprocessable();
    DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $data['paper']->id, 'status' => 'approved', 'created_at' => now(), 'updated_at' => now()]);
    $this->postJson('/api/v1/exam-papers/'.$data['paper']->id.'/submit')->assertConflict();
    $this->assertDatabaseHas('exam_paper_submissions', ['exam_paper_id' => $data['paper']->id, 'status' => 'approved']);
});

it('does not combine unrelated class and subject assignments in dashboard averages', function () {
    $data = mobileFixture();
    $secondClass = SchoolClass::create(['school_id' => $data['school']->id, 'name' => 'S3', 'education_stage' => 'secondary', 'sort_order' => 2]);
    $secondSubject = Subject::create(['school_id' => $data['school']->id, 'name' => 'English', 'code' => 'ENG']);
    DB::table('staff_subjects')->insert(['school_id' => $data['school']->id, 'term_id' => $data['term']->id, 'user_id' => $data['teacher']->id, 'subject_id' => $secondSubject->id, 'school_class_id' => $secondClass->id, 'created_at' => now(), 'updated_at' => now()]);
    $exam = Exam::create(['school_id' => $data['school']->id, 'term_id' => $data['term']->id, 'school_class_id' => $secondClass->id, 'name' => 'Unassigned maths', 'published_at' => now()]);
    $paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $data['subject']->id, 'maximum_score' => 100, 'weighting' => 1]);
    DB::table('exam_marks')->insert(['exam_paper_id' => $paper->id, 'student_id' => $data['student']->id, 'score' => 99, 'entered_by' => $data['teacher']->id, 'created_at' => now(), 'updated_at' => now()]);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.analytics.performance_labels', []);
});

it('limits the native class directory to teachers allowed to view learners', function () {
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->getJson('/api/v1/teacher/students')->assertForbidden();
    $data['class']->update(['class_teacher_user_id' => $data['teacher']->id]);
    $response = $this->getJson('/api/v1/teacher/students')->assertOk();
    expect(collect($response->json('data.data'))->pluck('id')->all())->toContain($data['student']->id)->not->toContain($data['foreign']->id);
    $this->getJson('/api/v1/teacher/students?search=Linked%20Learner')->assertOk()->assertJsonCount(2, 'data.data');
    Sanctum::actingAs($data['parent'], ['mobile']);
    $this->getJson('/api/v1/teacher/students')->assertForbidden();
});

it('serves native subjects and protects school tool actions', function () {
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->getJson('/api/v1/teacher/tools/subjects.index')->assertOk()->assertJsonPath('data.rows.0.title', 'Mathematics');
    $this->getJson('/api/v1/teacher/tools/payroll.index')->assertForbidden();
    $this->postJson('/api/v1/teacher/tools/events.index', ['action' => 'save_event'])->assertForbidden();
    $data['teacher']->designation->update(['permissions' => ['academics.events', 'students.manage', 'parents.manage']]);
    $data['teacher']->unsetRelation('designation');
    $this->postJson('/api/v1/teacher/tools/events.index', ['action' => 'save_event', 'title' => 'Sports day', 'term_id' => $data['term']->id, 'event_date' => today()->toDateString(), 'type' => 'sports', 'target_audience' => 'all'])->assertOk();
    $this->postJson('/api/v1/teacher/tools/student-categories.index', ['action' => 'add', 'name' => 'Boarding'])->assertOk();
    $this->assertDatabaseHas('student_categories', ['school_id' => $data['school']->id, 'name' => 'Boarding']);
    $this->postJson('/api/v1/teacher/tools/parents.register', ['action' => 'save', 'name' => 'Native parent', 'email' => 'native.parent@example.test', 'phone' => '', 'relationship' => 'Parent', 'password' => 'long-enough-password', 'studentIds' => [$data['foreign']->id]])->assertUnprocessable();
    $this->postJson('/api/v1/teacher/tools/parents.register', ['action' => 'save', 'name' => 'Native parent', 'email' => 'native.parent@example.test', 'phone' => '', 'relationship' => 'Parent', 'password' => 'long-enough-password', 'studentIds' => [$data['student']->id]])->assertOk();
});

it('uses the shared exam report calculation for native results', function () {
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->getJson('/api/v1/teacher/exams')->assertOk()->assertJsonCount(1, 'data');
    $this->getJson('/api/v1/teacher/exams/'.$data['paper']->exam_id)->assertOk()->assertJsonPath('data.readiness.all_papers_approved', false)->assertJsonCount(2, 'data.learners');
});

it('renders native teacher report modules with the website scopes', function () {
    $data = mobileFixture();
    $data['teacher']->designation->update(['permissions' => ['reports.view', 'attendance.reports', 'academics.events']]);
    $data['class']->update(['class_teacher_user_id' => $data['teacher']->id]);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    foreach (['reports.index', 'reports.student-term-report', 'reports.bulk-term-reports', 'attendance.reports', 'events.index'] as $tool) {
        $this->getJson('/api/v1/teacher/tools/'.$tool)->assertOk()->assertJsonStructure(['data' => ['rows', 'forms']]);
    }
});

it('downloads homework attachments only within the teachers own assignments', function () {
    $data = mobileFixture();
    \Illuminate\Support\Facades\Storage::fake('local');
    \Illuminate\Support\Facades\Storage::disk('local')->put('homework/example.txt', 'Homework content');
    $assignment = HomeworkAssignment::create(['school_id' => $data['school']->id, 'term_id' => $data['term']->id, 'teacher_id' => $data['teacher']->id, 'school_class_id' => $data['class']->id, 'subject_id' => $data['subject']->id, 'title' => 'Native attachment', 'instructions' => 'Read', 'maximum_score' => 10, 'due_at' => now()->addDay(), 'published_at' => now(), 'attachment_path' => 'homework/example.txt', 'attachment_name' => 'example.txt']);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->get('/api/v1/homework/'.$assignment->id.'/attachment')->assertOk()->assertDownload('example.txt');
    $otherTeacher = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);
    Sanctum::actingAs($otherTeacher, ['mobile']);
    $this->getJson('/api/v1/homework/'.$assignment->id.'/attachment')->assertForbidden();
});


it('provides a native response for every permission-granted workspace tool', function () {
    $data = mobileFixture();
    $data['teacher']->designation->update(['permissions' => array_keys(\App\Support\DesignationPermissions::groups())]);
    $data['class']->update(['class_teacher_user_id' => $data['teacher']->id]);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $tools = \App\Support\MobileTeacherWorkspace::forUser($data['teacher'])['tools'];
    foreach ($tools as $tool) {
        if ($tool['native'] || $tool['id'] === 'exams.results') continue;
        $this->getJson('/api/v1/teacher/tools/'.$tool['id'])->assertOk()->assertJsonStructure(['data' => ['title', 'rows', 'forms', 'page', 'last_page']]);
    }
});

it('keeps native profile editing behind learner-management permission', function () {
    $data = mobileFixture();
    $data['class']->update(['class_teacher_user_id' => $data['teacher']->id]);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->getJson('/api/v1/teacher/students/'.$data['student']->id)->assertOk()->assertJsonPath('data.can_edit', false);
    $this->postJson('/api/v1/teacher/students/'.$data['student']->id, ['name' => 'Changed'])->assertForbidden();
    $this->getJson('/api/v1/teacher/students/'.$data['foreign']->id)->assertNotFound();
});

it('lets a teacher update only their own profile fields', function () {
    Notification::fake();
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->postJson('/api/v1/auth/profile', [
        'name' => 'Updated Teacher', 'email' => 'updated@mobile.test', 'phone' => '+256700123456',
        'id' => $data['parent']->id, 'role' => 'school_admin', 'school_id' => $data['foreign']->school_id,
    ])->assertOk()->assertJsonPath('data.name', 'Updated Teacher')->assertJsonPath('data.phone', '+256700123456')->assertJsonPath('data.role', 'teacher');
    expect($data['teacher']->fresh()->school_id)->toBe($data['school']->id);
    expect($data['teacher']->fresh()->email_verified_at)->toBeNull();
    expect($data['parent']->fresh()->email)->not->toBe('updated@mobile.test');
    Notification::assertSentTo($data['teacher'], \App\Notifications\QueuedVerifyEmail::class);
});

it('validates profile changes and rejects non teacher writes', function () {
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->postJson('/api/v1/auth/profile', ['name' => '', 'email' => 'invalid'])->assertUnprocessable();
    $this->postJson('/api/v1/auth/profile', ['name' => 'Teacher', 'email' => $data['parent']->email])->assertUnprocessable();
    expect($data['teacher']->fresh()->email)->toBe('teacher@mobile.test');
    Sanctum::actingAs($data['parent'], ['mobile']);
    $this->postJson('/api/v1/auth/profile', ['name' => 'Parent', 'email' => 'parent@mobile.test'])->assertForbidden();
});

it('stores a teacher profile photo and rejects invalid uploads', function () {
    \Illuminate\Support\Facades\Storage::fake('public');
    $data = mobileFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->post('/api/v1/auth/profile', [
        'name' => $data['teacher']->name, 'email' => $data['teacher']->email,
        'photo' => \Illuminate\Http\UploadedFile::fake()->image('avatar.jpg'),
    ], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['data' => ['avatar_url']]);
    $path = $data['teacher']->fresh()->avatar_path;
    \Illuminate\Support\Facades\Storage::disk('public')->assertExists($path);
    $this->post('/api/v1/auth/profile', [
        'name' => $data['teacher']->name, 'email' => $data['teacher']->email,
        'photo' => \Illuminate\Http\UploadedFile::fake()->create('invalid.pdf', 10, 'application/pdf'),
    ], ['Accept' => 'application/json'])->assertUnprocessable();
    expect($data['teacher']->fresh()->avatar_path)->toBe($path);
});

it('filters student homework using only their own submissions', function () {
    $data = mobileFixture();
    $assignment = HomeworkAssignment::create(['school_id' => $data['school']->id, 'term_id' => $data['term']->id, 'teacher_id' => $data['teacher']->id, 'school_class_id' => $data['class']->id, 'subject_id' => $data['subject']->id, 'title' => 'Student work', 'instructions' => 'Solve', 'maximum_score' => 10, 'due_at' => now()->addDay(), 'published_at' => now()]);
    HomeworkSubmission::create(['homework_assignment_id' => $assignment->id, 'student_id' => $data['unlinked']->id, 'submitted_by' => $data['studentUser']->id, 'answer' => 'Private other answer', 'status' => 'reviewed', 'submitted_at' => now()]);
    Sanctum::actingAs($data['studentUser'], ['mobile']);
    $this->getJson('/api/v1/homework?status=pending')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonCount(0, 'data.data.0.submissions')->assertDontSee('Private other answer');
    $this->postJson('/api/v1/homework/'.$assignment->id.'/submit', ['answer' => 'My answer'])->assertOk();
    $this->getJson('/api/v1/homework?status=pending')->assertOk()->assertJsonCount(0, 'data.data');
    $this->getJson('/api/v1/homework?status=submitted')->assertOk()->assertJsonCount(1, 'data.data')->assertJsonPath('data.data.0.submissions.0.answer', 'My answer');
    $this->getJson('/api/v1/homework?status=reviewed')->assertOk()->assertJsonCount(0, 'data.data');
    $this->postJson('/api/v1/homework/'.$assignment->id.'/submit', ['answer' => 'Impersonation', 'student_id' => $data['unlinked']->id])->assertNotFound();
    Sanctum::actingAs($data['parent'], ['mobile']);
    $this->postJson('/api/v1/homework/'.$assignment->id.'/submit', ['answer' => 'Parent answer'])->assertForbidden();
});

it('accepts student homework attachments and clears stale review on resubmission', function () {
    \Illuminate\Support\Facades\Storage::fake('local');
    $data = mobileFixture();
    $assignment = HomeworkAssignment::create(['school_id' => $data['school']->id, 'term_id' => $data['term']->id, 'teacher_id' => $data['teacher']->id, 'school_class_id' => $data['class']->id, 'subject_id' => $data['subject']->id, 'title' => 'Student attachment', 'instructions' => 'Upload your work', 'maximum_score' => 10, 'due_at' => now()->subDay(), 'published_at' => now()]);
    Sanctum::actingAs($data['studentUser'], ['mobile']);
    $this->postJson('/api/v1/homework/'.$assignment->id.'/submit', ['answer' => ''])->assertUnprocessable();
    $this->post('/api/v1/homework/'.$assignment->id.'/submit', ['attachment' => \Illuminate\Http\UploadedFile::fake()->create('answer.txt', 1, 'text/plain')], ['Accept' => 'application/json'])->assertOk()->assertJsonPath('data.status', 'late');
    $submission = HomeworkSubmission::where('student_id', $data['student']->id)->firstOrFail();
    \Illuminate\Support\Facades\Storage::disk('local')->assertExists($submission->attachment_path);
    $submission->update(['status' => 'reviewed', 'score' => 8, 'feedback' => 'Good', 'reviewed_at' => now()]);
    $this->postJson('/api/v1/homework/'.$assignment->id.'/submit', ['answer' => 'Revised', 'base_version' => now()->subHour()->toISOString()])->assertConflict();
    $this->postJson('/api/v1/homework/'.$assignment->id.'/submit', ['answer' => 'Revised', 'base_version' => $submission->fresh()->updated_at->toISOString()])->assertOk()->assertJsonPath('data.score', null)->assertJsonPath('data.feedback', null)->assertJsonPath('data.status', 'late');
    $this->get('/api/v1/homework/'.$assignment->id.'/submissions/'.$submission->id.'/attachment')->assertOk()->assertDownload('answer.txt');
});
