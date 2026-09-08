<?php

use App\Models\AttendanceRecord;
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

beforeEach(function () {
    $this->school = School::create(['name' => 'Offline School', 'slug' => 'offline-school', 'status' => 'active', 'is_demo' => false]);
    $this->term = Term::create(['school_id' => $this->school->id, 'name' => 'Term One', 'year' => 2026, 'term_number' => 1, 'is_current' => true, 'status' => 'open']);
    $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary One']);
    $this->student = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'term_id' => $this->term->id, 'name' => 'Offline Learner', 'status' => 'active']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Math']);
    $exam = Exam::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'name' => 'Midterm']);
    $this->paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100, 'weighting' => 1]);
    $this->attendanceUrl = '/offline-data/download?kind=attendance&target='.$this->class->id.'&date=2026-09-07';
    $this->marksUrl = '/offline-data/download?kind=marks&target='.$this->paper->id;
});

test('offline data requires authentication and is not cacheable', function () {
    $this->getJson('/offline-data/catalog')->assertUnauthorized();
    $response = $this->actingAs($this->admin)->getJson('/offline-data/catalog')->assertOk()->assertJsonPath('classes.0.id', $this->class->id);
    expect($response->headers->get('Cache-Control'))->toContain('no-store');
});

test('attendance retries are idempotent and later edits use the refreshed baseline', function () {
    $token = $this->actingAs($this->admin)->getJson($this->attendanceUrl)->assertOk()->json('token');
    $payload = ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => 'present']]];
    $fresh = $this->postJson('/offline-data/sync', $payload)->assertOk()->assertJsonPath('rows.0.value', 'present');
    $this->postJson('/offline-data/sync', $payload)->assertOk();
    $this->assertDatabaseCount('attendance_records', 1);
    $payload['token'] = $fresh->json('token');
    $payload['changes'][0]['value'] = 'absent';
    $this->postJson('/offline-data/sync', $payload)->assertOk();
    $this->assertDatabaseHas('attendance_records', ['student_id' => $this->student->id, 'status' => 'absent']);
});

test('new online attendance is not overwritten by an offline snapshot of an empty register', function () {
    $token = $this->actingAs($this->admin)->getJson($this->attendanceUrl)->json('token');
    AttendanceRecord::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'student_id' => $this->student->id, 'attendance_date' => '2026-09-07', 'session_key' => 'daily', 'status' => 'late']);
    $this->postJson('/offline-data/sync', ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => 'present']]])->assertConflict();
    $this->assertDatabaseHas('attendance_records', ['student_id' => $this->student->id, 'status' => 'late']);
});

test('sync rejects tampered snapshots and another account', function () {
    $token = $this->actingAs($this->admin)->getJson($this->attendanceUrl)->json('token');
    $payload = ['token' => $token.'tamper', 'changes' => [['id' => $this->student->id, 'value' => 'present']]];
    $this->postJson('/offline-data/sync', $payload)->assertUnprocessable();
    $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $payload['token'] = $token;
    $this->actingAs($other)->postJson('/offline-data/sync', $payload)->assertForbidden();
    $this->assertDatabaseCount('attendance_records', 0);
});

test('sync rechecks permissions and term status', function () {
    $teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $this->class->update(['class_teacher_user_id' => $teacher->id]);
    $token = $this->actingAs($teacher)->getJson($this->attendanceUrl)->assertOk()->json('token');
    $this->class->update(['class_teacher_user_id' => null]);
    $payload = ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => 'present']]];
    $this->postJson('/offline-data/sync', $payload)->assertForbidden();
    $token = $this->actingAs($this->admin)->getJson($this->attendanceUrl)->json('token');
    $this->term->update(['status' => 'closed']);
    $payload['token'] = $token;
    $this->postJson('/offline-data/sync', $payload)->assertUnprocessable();
});

test('cross school classes and students cannot be downloaded or synced', function () {
    $otherSchool = School::create(['name' => 'Other', 'slug' => 'other']);
    $otherClass = SchoolClass::create(['school_id' => $otherSchool->id, 'name' => 'Other class']);
    $otherStudent = Student::create(['school_id' => $otherSchool->id, 'school_class_id' => $otherClass->id, 'name' => 'Other learner', 'status' => 'active']);
    $this->actingAs($this->admin)->getJson('/offline-data/download?kind=attendance&target='.$otherClass->id.'&date=2026-09-07')->assertNotFound();
    $token = $this->getJson($this->attendanceUrl)->json('token');
    $this->postJson('/offline-data/sync', ['token' => $token, 'changes' => [['id' => $otherStudent->id, 'value' => 'present']]])->assertConflict();
});

test('marks sync as drafts with decimal values and can be edited again', function () {
    $token = $this->actingAs($this->admin)->getJson($this->marksUrl)->assertOk()->json('token');
    foreach ([75, 76.25, null, 80] as $value) {
        $fresh = $this->postJson('/offline-data/sync', ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => $value]]])->assertOk();
        expect($fresh->json('rows.0.value'))->toEqual($value);
        $token = $fresh->json('token');
    }
    $this->assertDatabaseCount('exam_marks', 1);
    expect(DB::table('exam_paper_submissions')->where('exam_paper_id', $this->paper->id)->value('status'))->toBeNull();
});

test('marks reject excessive scores and submitted papers', function () {
    $token = $this->actingAs($this->admin)->getJson($this->marksUrl)->json('token');
    $payload = ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => 101]]];
    $this->postJson('/offline-data/sync', $payload)->assertUnprocessable();
    DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $this->paper->id, 'status' => 'submitted']);
    $payload['changes'][0]['value'] = 50;
    $this->postJson('/offline-data/sync', $payload)->assertConflict();
    $this->assertDatabaseCount('exam_marks', 0);
});

test('a conflict rolls back all changes in a sync batch', function () {
    $second = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'Second learner', 'status' => 'active']);
    $token = $this->actingAs($this->admin)->getJson($this->marksUrl)->json('token');
    DB::table('exam_marks')->insert(['exam_paper_id' => $this->paper->id, 'student_id' => $second->id, 'score' => 90, 'updated_at' => now()]);
    $this->postJson('/offline-data/sync', ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => 50], ['id' => $second->id, 'value' => 60]]])->assertConflict();
    $this->assertDatabaseCount('exam_marks', 1);
    $this->assertDatabaseHas('exam_marks', ['student_id' => $second->id, 'score' => 90]);
});

test('unrelated online edits are preserved while changed learners sync', function () {
    $second = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'Second learner', 'status' => 'active']);
    $token = $this->actingAs($this->admin)->getJson($this->marksUrl)->json('token');
    DB::table('exam_marks')->insert(['exam_paper_id' => $this->paper->id, 'student_id' => $second->id, 'score' => 90]);
    $this->postJson('/offline-data/sync', ['token' => $token, 'changes' => [['id' => $this->student->id, 'value' => 50]]])->assertOk();
    $this->assertDatabaseHas('exam_marks', ['student_id' => $second->id, 'score' => 90]);
});
