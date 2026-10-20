<?php

use App\Livewire\StudentConductLog;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentConduct;
use App\Models\StudentGuardian;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Conduct School', 'slug' => 'conduct-school']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->classTeacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'P5', 'class_teacher_user_id' => $this->classTeacher->id]);
    $this->student = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'Conduct Learner', 'status' => 'active']);
    $this->sibling = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'Other Child', 'status' => 'active']);
    $this->parent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    $this->parent->portalStudents()->attach([$this->student->id => ['school_id' => $this->school->id], $this->sibling->id => ['school_id' => $this->school->id]]);
    $this->guardian = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    StudentGuardian::create(['student_id' => $this->student->id, 'name' => 'Guardian', 'email' => $this->guardian->email, 'relationship' => 'Mother']);
});

it('lets the class teacher record good and bad conduct and notifies only linked parents', function () {
    $otherParent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    $studentUser = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
    $studentUser->portalStudents()->attach($this->student->id, ['school_id' => $this->school->id]);
    $component = Livewire::actingAs($this->classTeacher)->test(StudentConductLog::class, ['student' => $this->student]);
    $component->assertSet('occurredOn', today()->toDateString())
        ->set('kind', 'bad')->set('occurredOn', today()->subDays(3)->toDateString())
        ->set('description', 'Abused a teacher')->set('action', 'Three-day suspension')->call('save')->assertHasNoErrors();
    $record = StudentConduct::firstOrFail();
    expect($record->kind)->toBe('bad')->and($record->recorded_by)->toBe($this->classTeacher->id);
    foreach ([$this->parent, $this->guardian] as $parent) {
        $this->assertDatabaseHas('school_notifications', ['user_id' => $parent->id, 'student_id' => $this->student->id, 'category' => 'discipline', 'title' => 'Bad conduct recorded']);
    }
    $this->assertDatabaseMissing('school_notifications', ['user_id' => $otherParent->id]);
    $this->assertDatabaseMissing('school_notifications', ['user_id' => $studentUser->id]);
    $component->set('kind', 'good')->set('description', 'Helped another learner')->set('action', 'Merit certificate')->call('save')->assertHasNoErrors();
    expect(StudentConduct::count())->toBe(2);
    $this->assertDatabaseHas('school_notifications', ['user_id' => $this->parent->id, 'title' => 'Good conduct recorded']);
});

it('rejects future dates missing descriptions and invalid classifications', function () {
    Livewire::actingAs($this->admin)->test(StudentConductLog::class, ['student' => $this->student])
        ->set('occurredOn', today()->addDay()->toDateString())->set('description', 'An incident')->call('save')->assertHasErrors(['occurredOn'])
        ->set('occurredOn', today()->toDateString())->set('description', '   ')->set('kind', 'other')->call('save')->assertHasErrors(['description', 'kind']);
    $this->assertDatabaseCount('student_conducts', 0);
    $this->assertDatabaseCount('school_notifications', 0);
});

it('keeps records view-only for other staff even if they can manage learner details', function () {
    $staff = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar']);
    $designation = \App\Models\Designation::create(['school_id' => $this->school->id, 'name' => 'Registrar', 'permissions' => ['students.view', 'students.manage']]);
    $staff->update(['designation_id' => $designation->id]);
    $record = StudentConduct::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'occurred_on' => today(), 'kind' => 'good', 'description' => 'Excellent teamwork', 'recorded_by' => $this->admin->id]);
    Livewire::actingAs($staff)->test(StudentConductLog::class, ['student' => $this->student])
        ->assertSee('Excellent teamwork')->assertDontSee('Save conduct')->call('edit', $record->id)->assertForbidden();
    Livewire::actingAs($staff)->test(StudentConductLog::class, ['student' => $this->student])
        ->set('description', 'Unauthorized write')->call('save')->assertForbidden();
    Livewire::actingAs($this->parent)->test(StudentConductLog::class, ['student' => $this->student])->assertForbidden();
});

it('allows admin edits and updates the parent notification without duplicating it', function () {
    $component = Livewire::actingAs($this->classTeacher)->test(StudentConductLog::class, ['student' => $this->student])
        ->set('kind', 'bad')->set('description', 'Disrupted class')->set('action', 'Suspension')->call('save');
    $record = StudentConduct::firstOrFail();
    $notification = DB::table('school_notifications')->where('user_id', $this->parent->id)->first();
    DB::table('school_notification_reads')->insert(['school_notification_id' => $notification->id, 'user_id' => $this->parent->id, 'read_at' => now(), 'created_at' => now(), 'updated_at' => now()]);
    Livewire::actingAs($this->admin)->test(StudentConductLog::class, ['student' => $this->student])
        ->call('edit', $record->id)->assertSet('action', 'Suspension')->set('action', 'Written warning')->call('save')->assertHasNoErrors();
    expect($record->fresh()->action)->toBe('Written warning')->and($record->fresh()->updated_by)->toBe($this->admin->id)
        ->and(DB::table('school_notifications')->where('user_id', $this->parent->id)->count())->toBe(1);
    $this->assertDatabaseMissing('school_notification_reads', ['school_notification_id' => $notification->id, 'user_id' => $this->parent->id]);
    $this->assertDatabaseHas('school_notifications', ['id' => $notification->id, 'title' => 'Bad conduct updated']);
});

it('shows only the selected childs conduct on the parent dashboard', function () {
    StudentConduct::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'occurred_on' => today(), 'kind' => 'bad', 'description' => 'Learner incident detail', 'action' => 'Written warning']);
    StudentConduct::create(['school_id' => $this->school->id, 'student_id' => $this->sibling->id, 'occurred_on' => today(), 'kind' => 'good', 'description' => 'Sibling achievement detail', 'action' => 'Award']);
    $this->actingAs($this->parent)->get(route('portal.home', ['student' => $this->student->id]))->assertOk()
        ->assertSee('Learner incident detail')->assertSee('Written warning')->assertDontSee('Sibling achievement detail')->assertDontSee('Edit entry');
    $this->get(route('portal.home', ['student' => $this->sibling->id]))->assertOk()
        ->assertSee('Sibling achievement detail')->assertDontSee('Learner incident detail');
    $this->actingAs($this->guardian)->get(route('portal.home'))->assertOk()->assertSee('Learner incident detail');
});

it('prevents a class teacher editing another learners record or crossing schools', function () {
    $otherClass = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Other class']);
    $other = Student::create(['school_id' => $this->school->id, 'school_class_id' => $otherClass->id, 'name' => 'Unassigned learner', 'status' => 'active']);
    $record = StudentConduct::create(['school_id' => $this->school->id, 'student_id' => $other->id, 'occurred_on' => today(), 'kind' => 'bad', 'description' => 'Other learner incident']);
    Livewire::actingAs($this->classTeacher)->test(StudentConductLog::class, ['student' => $this->student])->call('edit', $record->id)->assertNotFound();
    Livewire::actingAs($this->classTeacher)->test(StudentConductLog::class, ['student' => $other])->assertForbidden();
    $foreign = School::create(['name' => 'Another school', 'slug' => 'another-conduct-school']);
    $foreignStudent = Student::create(['school_id' => $foreign->id, 'name' => 'Foreign learner', 'status' => 'active']);
    Livewire::actingAs($this->admin)->test(StudentConductLog::class, ['student' => $foreignStudent])->assertForbidden();
});

it('gives the assigned subject teacher read-only access', function () {
    $teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $subject = \App\Models\Subject::create(['school_id' => $this->school->id, 'name' => 'Maths', 'code' => 'MATH']);
    DB::table('staff_subjects')->insert(['school_id' => $this->school->id, 'user_id' => $teacher->id, 'school_class_id' => $this->class->id, 'subject_id' => $subject->id]);
    StudentConduct::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'occurred_on' => today(), 'kind' => 'good', 'description' => 'Excellent leadership']);
    Livewire::actingAs($teacher)->test(StudentConductLog::class, ['student' => $this->student])
        ->assertSee('Excellent leadership')->assertDontSee('Edit entry')->assertDontSee('Save conduct')
        ->set('description', 'Cannot change it')->call('save')->assertForbidden();
});

it('uses the school timezone for today and the future-date limit', function () {
    $this->travelTo(\Illuminate\Support\Carbon::parse('2026-05-01 22:30:00', 'UTC'), function () {
        \App\Models\SchoolSetting::create(['school_id' => $this->school->id, 'key' => 'timezone', 'value' => 'Africa/Kampala']);
        Livewire::actingAs($this->admin)->test(StudentConductLog::class, ['student' => $this->student])
            ->assertSet('occurredOn', '2026-05-02')->set('description', 'Helped a classmate')->call('save')->assertHasNoErrors()
            ->set('occurredOn', '2026-05-03')->set('description', 'Future conduct')->call('save')->assertHasErrors(['occurredOn']);
        $this->assertDatabaseCount('student_conducts', 1);
    });
});
