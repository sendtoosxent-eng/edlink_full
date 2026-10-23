<?php

use App\Livewire\ExamSetup;
use App\Models\Designation;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Support\MobileTeacherWorkspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Task School', 'slug' => 'task-school', 'license_status' => 'active', 'license_expires_at' => now()->addYear()]);
    $designation = Designation::create(['school_id' => $this->school->id, 'name' => 'Class Teacher', 'permissions' => ['attendance.daily', 'attendance.subject', 'exams.marks']]);
    $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher', 'designation_id' => $designation->id]);
    $this->term = Term::create(['school_id' => $this->school->id, 'name' => 'Term 3', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Senior 2', 'class_teacher_user_id' => $this->teacher->id]);
    $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MAT']);
    DB::table('staff_subjects')->insert(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'user_id' => $this->teacher->id]);
    DB::table('class_subjects')->insert(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id]);
    $this->learners = collect([1, 2])->map(fn ($number) => Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'Learner '.$number, 'admission_no' => 'TASK-'.$number, 'status' => 'active']));
    $this->exam = Exam::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'name' => 'End of term']);
    $this->paper = ExamPaper::create(['exam_id' => $this->exam->id, 'subject_id' => $this->subject->id, 'maximum_score' => 100, 'weighting' => 1]);
    Sanctum::actingAs($this->teacher, ['mobile']);
});

it('shows only held exams with unsubmitted marks for the assigned teacher', function () {
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toBe([]);
    $this->exam->update(['completed_at' => now()]);
    $pending = MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id);
    expect($pending)->toHaveCount(1)->and($pending[0])->toMatchArray(['paper_id' => $this->paper->id, 'subject_name' => 'Mathematics', 'exam_name' => 'End of term', 'class_name' => 'Senior 2']);
    $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.analytics.stats.pending_marks', 1)->assertJsonPath('data.teacher_workspace.pending_results.0.paper_id', $this->paper->id);
    $this->exam->update(['completed_at' => now()->addDay()]);
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toBe([]);
});

it('recognizes existing draft marks and clears the task after a real submission', function () {
    foreach ($this->learners as $student) DB::table('exam_marks')->insert(['exam_paper_id' => $this->paper->id, 'student_id' => $student->id, 'score' => 75, 'entered_by' => $this->teacher->id, 'created_at' => now(), 'updated_at' => now()]);
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toHaveCount(1);
    $this->postJson('/api/v1/exam-papers/'.$this->paper->id.'/submit')->assertOk();
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toBe([]);
    DB::table('exam_paper_submissions')->where('exam_paper_id', $this->paper->id)->update(['status' => 'approved']);
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toBe([]);
});

it('keeps partially recorded daily registers pending until every active learner is marked', function () {
    $tasks = fn () => collect(MobileTeacherWorkspace::forUser($this->teacher)['pending_attendance'])->where('attendance_type', 'daily')->values();
    expect($tasks()->first()['remaining'])->toBe(2);
    foreach ($this->learners as $index => $student) {
        DB::table('attendance_records')->insert(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'student_id' => $student->id, 'attendance_date' => today(), 'status' => 'present', 'recorded_by' => $this->teacher->id, 'session_key' => 'daily']);
        if ($index === 0) expect($tasks()->first()['remaining'])->toBe(1);
    }
    expect($tasks())->toHaveCount(0);
});

it('hides tasks outside the current term school and teaching assignments', function () {
    $this->exam->update(['completed_at' => now()]);
    $other = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    expect(MobileTeacherWorkspace::pendingResults($other, $this->term->id))->toBe([]);
    $oldTerm = Term::create(['school_id' => $this->school->id, 'name' => 'Term 2', 'year' => 2026, 'is_current' => false]);
    $this->exam->update(['term_id' => $oldTerm->id]);
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toBe([]);
    $this->exam->update(['term_id' => $this->term->id, 'published_at' => now()]);
    expect(MobileTeacherWorkspace::pendingResults($this->teacher, $this->term->id))->toBe([]);
    $this->term->update(['status' => 'closed']);
    $workspace = MobileTeacherWorkspace::forUser($this->teacher);
    expect($workspace['pending_attendance'])->toBe([])->and($workspace['pending_results'])->toBe([]);
});

it('lets exam managers mark exams completed and enforces school boundaries', function () {
    $manager = User::factory()->create(['school_id' => $this->school->id, 'role' => 'superadmin']);
    Livewire::actingAs($manager)->test(ExamSetup::class)->call('markCompleted', $this->exam->id)->assertHasNoErrors();
    expect($this->exam->fresh()->completed_at)->not->toBeNull();
    $this->assertDatabaseHas('audit_logs', ['event' => 'exam.completed', 'subject_id' => $this->exam->id]);
    $foreign = School::create(['name' => 'Foreign', 'slug' => 'foreign-task-school']);
    $foreignManager = User::factory()->create(['school_id' => $foreign->id, 'role' => 'superadmin']);
    Livewire::actingAs($foreignManager)->test(ExamSetup::class)->call('markCompleted', $this->exam->id)->assertNotFound();
    Livewire::actingAs($this->teacher)->test(ExamSetup::class)->assertForbidden();
});


it('keeps a partially recorded subject register pending and ignores empty rosters', function () {
    $tasks = fn () => collect(MobileTeacherWorkspace::forUser($this->teacher)['pending_attendance'])->where('attendance_type', 'subject')->values();
    $session = $tasks()->first()['session_key'];
    foreach ($this->learners as $index => $student) {
        DB::table('attendance_records')->insert(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'student_id' => $student->id, 'attendance_date' => today(), 'status' => 'present', 'recorded_by' => $this->teacher->id, 'session_key' => $session]);
        if ($index === 0) expect($tasks()->first()['remaining'])->toBe(1);
    }
    expect($tasks())->toHaveCount(0);
    foreach ($this->learners as $student) $student->update(['status' => 'inactive']);
    expect(MobileTeacherWorkspace::forUser($this->teacher)['pending_attendance'])->toBe([]);
});
