<?php

use App\Livewire\Leaves;
use App\Models\{Designation, Exam, ExamPaper, School, SchoolClass, StaffLeave, Student, Subject, Term, User};
use App\Services\LeaveCoverService;
use App\Support\{MobileTeacherWorkspace, TeacherAcademicScope};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\{DB, Mail};
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->travelTo(now()->setDate(2026, 10, 22)->setTime(10, 0));
    Mail::fake();
    $this->school = School::create(['name' => 'Leave Cover School', 'slug' => 'leave-cover-school', 'status' => 'active', 'license_status' => 'active', 'license_expires_at' => now()->addYear()]);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $this->cover = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $this->term = Term::create(['school_id' => $this->school->id, 'name' => 'Current Term', 'year' => 2026, 'term_number' => 3, 'status' => 'open', 'is_current' => true]);
    $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'P5', 'class_teacher_user_id' => $this->teacher->id]);
    $this->subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
    DB::table('staff_subjects')->insert(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id]);
    $this->slot = DB::table('timetable_slots')->insertGetId(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'subject_id' => $this->subject->id, 'day_of_week' => 'Thursday', 'starts_at' => '08:00:00', 'ends_at' => '09:00:00']);
    $this->learner = Student::create(['school_id' => $this->school->id, 'name' => 'Covered Learner', 'school_class_id' => $this->class->id, 'admission_no' => 'COV-1', 'status' => 'active']);
    $exam = Exam::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'term_id' => $this->term->id, 'name' => 'Covered Exam']);
    $this->paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $this->subject->id, 'maximum_score' => 100]);
    $this->leave = StaffLeave::create(['school_id' => $this->school->id, 'user_id' => $this->teacher->id, 'type' => 'Sick Leave', 'starts_on' => today(), 'ends_on' => today()->addDays(2), 'status' => 'pending']);
    $this->actingAs($this->admin);
});

it('requires cover before approving class and subject duties and never moves permanent assignments', function () {
    Livewire::test(Leaves::class)->call('approve', $this->leave->id)->assertSet('approvingLeaveId', $this->leave->id)->assertSet('handoverClassCount', 1)
        ->set('replacementStaffId', (string) $this->cover->id)->call('confirmApproval')->assertHasNoErrors();
    expect($this->leave->fresh()->status)->toBe('approved')->and($this->teacher->fresh()->isOnLeave())->toBeTrue();
    $this->assertDatabaseHas('school_classes', ['id' => $this->class->id, 'class_teacher_user_id' => $this->teacher->id]);
    $this->assertDatabaseHas('staff_subjects', ['user_id' => $this->teacher->id, 'school_class_id' => $this->class->id]);
    $this->assertDatabaseHas('timetable_slots', ['id' => $this->slot, 'user_id' => $this->teacher->id]);
    $this->assertDatabaseHas('staff_leave_handovers', ['staff_leave_id' => $this->leave->id, 'to_user_id' => $this->cover->id, 'temporary' => true]);
    expect(TeacherAcademicScope::classIds($this->cover)->all())->toContain($this->class->id);
    expect(TeacherAcademicScope::canEnterPaper($this->cover, $this->class->id, $this->subject->id, $this->term->id))->toBeTrue();
    Sanctum::actingAs($this->cover, ['mobile']);
    $this->getJson('/api/v1/teacher/students')->assertOk()->assertJsonPath('data.data.0.name', 'Covered Learner');
    $this->getJson('/api/v1/teacher/exams')->assertOk()->assertJsonPath('data.0.name', 'Covered Exam');
    $this->getJson('/api/v1/timetable')->assertOk()->assertJsonFragment(['id' => $this->slot]);
    $this->getJson('/api/v1/attendance/registers?attendance_date='.today()->toDateString())->assertOk()->assertJsonCount(2, 'data.registers');
    expect(MobileTeacherWorkspace::forUser($this->cover)['pending_attendance'])->toHaveCount(2);
    expect(MobileTeacherWorkspace::forUser($this->teacher)['pending_attendance'])->toBe([]);
});

it('covers class-teacher-only responsibilities even when the staff account is not a teacher role', function () {
    $this->teacher->update(['role' => 'academic_admin']);
    DB::table('staff_subjects')->where('user_id', $this->teacher->id)->delete();
    DB::table('timetable_slots')->where('user_id', $this->teacher->id)->delete();
    Livewire::test(Leaves::class)->call('approve', $this->leave->id)->assertSet('approvingLeaveId', $this->leave->id)->assertSet('handoverClassCount', 1);
    expect($this->leave->fresh()->status)->toBe('pending');
});

it('starts and expires cover access automatically while preserving the replacements own roles', function () {
    $this->leave->update(['starts_on' => today()->addDay(), 'ends_on' => today()->addDays(2)]);
    $own = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Own P6', 'class_teacher_user_id' => $this->cover->id]);
    app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin);
    expect($this->teacher->fresh()->isOnLeave())->toBeFalse();
    expect(TeacherAcademicScope::classIds($this->cover)->all())->not->toContain($this->class->id);
    $this->travelTo(now()->addDay());
    expect($this->teacher->fresh()->isOnLeave())->toBeTrue();
    expect(TeacherAcademicScope::classIds($this->cover)->all())->toContain($this->class->id, $own->id);
    $this->travelTo(now()->addDay()->endOfDay());
    expect($this->teacher->fresh()->isOnLeave())->toBeTrue();
    $this->travelTo(now()->addDay()->startOfDay());
    expect($this->teacher->fresh()->isOnLeave())->toBeFalse();
    expect(TeacherAcademicScope::classIds($this->cover)->all())->toBe([$own->id]);
    expect(TeacherAcademicScope::canEnterPaper($this->cover, $this->class->id, $this->subject->id, $this->term->id))->toBeFalse();
    expect($this->class->fresh()->class_teacher_user_id)->toBe($this->teacher->id);
});

it('ends temporary access immediately when an approver records an early return', function () {
    app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin);
    Livewire::test(Leaves::class)->call('markReturned', $this->leave->id)->assertHasNoErrors();
    expect($this->leave->fresh()->returned_at)->not->toBeNull();
    expect($this->teacher->fresh()->isOnLeave())->toBeFalse();
    expect(TeacherAcademicScope::classIds($this->cover))->toHaveCount(0);
});

it('rejects unavailable and foreign replacements and overlapping lesson times', function () {
    StaffLeave::create(['school_id' => $this->school->id, 'user_id' => $this->cover->id, 'type' => 'Annual', 'starts_on' => today()->addDay(), 'ends_on' => today()->addDay(), 'status' => 'approved']);
    expect(fn () => app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin))->toThrow(ValidationException::class);
    StaffLeave::where('user_id', $this->cover->id)->delete();
    DB::table('timetable_slots')->insert(['school_id' => $this->school->id, 'user_id' => $this->cover->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'day_of_week' => 'Thursday', 'starts_at' => '08:30:00', 'ends_at' => '09:30:00']);
    expect(fn () => app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin))->toThrow(ValidationException::class);
    $other = School::create(['name' => 'Other Leave School', 'slug' => 'other-leave-school']);
    $foreign = User::factory()->create(['school_id' => $other->id, 'role' => 'teacher']);
    expect(fn () => app(LeaveCoverService::class)->approve($this->leave, $foreign->id, $this->admin))->toThrow(ValidationException::class);
    expect($this->leave->fresh()->status)->toBe('pending');
});

it('grays absent teachers and prevents assigning them new class or subject duties', function () {
    app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin);
    Livewire::test(App\Livewire\StaffManagement::class)->assertSee('On leave');
    Livewire::test(App\Livewire\ClassesAndStreams::class)->call('assignClassTeacher', $this->class->id, (string) $this->teacher->id)->assertHasErrors('classTeachers');
    Livewire::test(App\Livewire\Subjects::class)->set('subjectId', (string) $this->subject->id)->set('classId', (string) $this->class->id)->set('teacherId', (string) $this->teacher->id)->call('assign')->assertHasErrors('teacherId');
});


it('gives subject-only cover scoped student and result access without a class-teacher role', function () {
    $this->class->update(['class_teacher_user_id' => null]);
    app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin);
    expect(TeacherAcademicScope::classIds($this->cover))->toHaveCount(0);
    expect($this->cover->hasPermission('attendance.daily'))->toBeFalse();
    expect(TeacherAcademicScope::canViewStudentDirectory($this->cover))->toBeTrue();
    expect(TeacherAcademicScope::canViewExam($this->cover, $this->class->id, $this->term->id))->toBeTrue();
    $other = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Uncovered class']);
    Student::create(['school_id' => $this->school->id, 'school_class_id' => $other->id, 'name' => 'Uncovered Learner', 'status' => 'active']);
    Sanctum::actingAs($this->cover, ['mobile']);
    $this->getJson('/api/v1/teacher/students')->assertOk()->assertJsonCount(1, 'data.data')->assertDontSee('Uncovered Learner');
    $this->getJson('/api/v1/exam-papers/'.$this->paper->id.'/marks')->assertOk();
    expect(TeacherAcademicScope::canViewExam($this->cover, $other->id, $this->term->id))->toBeFalse();
    $this->travelTo(now()->addDays(3));
    $this->getJson('/api/v1/exam-papers/'.$this->paper->id.'/marks')->assertForbidden();
    expect(TeacherAcademicScope::canViewStudentDirectory($this->cover))->toBeFalse();
});

it('allows cover changes and revokes the previous replacements temporary access immediately', function () {
    app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin);
    $next = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    Livewire::test(Leaves::class)->call('changeCover', $this->leave->id)->set('replacementStaffId', (string) $next->id)->call('confirmApproval')->assertHasNoErrors();
    expect(TeacherAcademicScope::classIds($this->cover))->toHaveCount(0);
    expect(TeacherAcademicScope::classIds($next)->all())->toContain($this->class->id);
    $this->assertDatabaseCount('staff_leave_handovers', 1);
    $this->assertDatabaseHas('audit_logs', ['event' => 'leave.cover_reassigned']);
});

it('keeps cover staffed when an administrator attempts to deactivate the replacement', function () {
    app(LeaveCoverService::class)->approve($this->leave, $this->cover->id, $this->admin);
    Livewire::test(App\Livewire\StaffManagement::class)->call('toggleStatus', $this->cover->id);
    expect($this->cover->fresh()->employment_status)->toBe('active');
});
