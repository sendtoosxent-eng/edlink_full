<?php

use App\Livewire\StaffManagement;
use App\Livewire\StaffRegister;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Notifications\QueuedVerifyEmail;
use Illuminate\Contracts\Notifications\Dispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Livewire\Volt\Volt;

uses(RefreshDatabase::class);

function staffRegistrationFixture(bool $withTerm = true): array
{
    $school = School::create([
        'name' => 'Registration Test School '.uniqid(),
        'slug' => 'registration-test-school-'.uniqid(),
        'status' => 'active',
        'license_status' => 'active',
        'license_expires_at' => now()->addYear(),
    ]);
    $term = $withTerm ? Term::create([
        'school_id' => $school->id,
        'name' => 'Term 1',
        'year' => 2026,
        'is_current' => true,
        'status' => 'open',
        'locked' => false,
    ]) : null;
    $classA = SchoolClass::create(['school_id' => $school->id, 'name' => 'P5 A', 'education_stage' => 'primary', 'sort_order' => 1]);
    $classB = SchoolClass::create(['school_id' => $school->id, 'name' => 'P6 B', 'education_stage' => 'primary', 'sort_order' => 2]);
    $math = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MATH']);
    $english = Subject::create(['school_id' => $school->id, 'name' => 'English', 'code' => 'ENG']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);

    return compact('school', 'term', 'classA', 'classB', 'math', 'english', 'admin');
}

function completeStaffRegistration(array $data, ?Designation $designation = null): Testable
{
    $component = Livewire::actingAs($data['admin'])->test(StaffRegister::class);
    $designation ??= Designation::where('school_id', $data['school']->id)->where('name', 'Class Teacher')->firstOrFail();

    return $component
        ->set('name', 'New Staff Member')
        ->set('email', ' NEW.STAFF@example.test ')
        ->set('phone', '+256700111222')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('next')->assertHasNoErrors()->assertSet('step', 2)
        ->set('role', 'teacher')
        ->set('designation_id', (string) $designation->id)
        ->set('job_title', 'Mathematics Teacher')
        ->set('base_salary', '450000')
        ->call('next')->assertHasNoErrors()->assertSet('step', 3)
        ->set('is_class_teacher', true)
        ->set('class_teacher_class_id', (string) $data['classA']->id)
        ->set('teaching_assignments', [
            ['class_id' => (string) $data['classA']->id, 'subject_ids' => [(string) $data['math']->id]],
            ['class_id' => (string) $data['classB']->id, 'subject_ids' => [(string) $data['math']->id, (string) $data['english']->id]],
        ]);
}

it('creates standard designations before staff onboarding begins', function () {
    $data = staffRegistrationFixture();

    Livewire::actingAs($data['admin'])->test(StaffRegister::class)->assertOk();

    expect(Designation::where('school_id', $data['school']->id)->pluck('name')->all())
        ->toContain('Bursar', 'DOS', 'Subject Teacher', 'Class Teacher');
});

it('keeps standard account types and designations in sync', function () {
    $data = staffRegistrationFixture();
    $component = Livewire::actingAs($data['admin'])->test(StaffRegister::class);
    $bursar = Designation::where('school_id', $data['school']->id)->where('name', 'Bursar')->firstOrFail();
    $subjectTeacher = Designation::where('school_id', $data['school']->id)->where('name', 'Subject Teacher')->firstOrFail();

    $component->set('role', 'bursar')
        ->assertSet('designation_id', (string) $bursar->id)
        ->assertSet('has_teaching_duties', false)
        ->set('designation_id', (string) $subjectTeacher->id)
        ->assertSet('role', 'teacher')
        ->assertSet('has_teaching_duties', true);
});

it('registers a class and subject teacher with salary, mappings, verification and working login', function () {
    Notification::fake();
    $data = staffRegistrationFixture();

    completeStaffRegistration($data)
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('staff.index'));

    $staff = User::where('school_id', $data['school']->id)->where('email', 'new.staff@example.test')->firstOrFail();
    expect($staff->base_salary)->toBe('450000.00')
        ->and(Hash::check('Password123!', $staff->password))->toBeTrue()
        ->and($data['classA']->fresh()->class_teacher_user_id)->toBe($staff->id);

    $this->assertDatabaseHas('users', [
        'id' => $staff->id,
        'role' => 'teacher',
        'staff_number' => 'STF-'.$data['school']->id.'-'.str_pad((string) $staff->id, 6, '0', STR_PAD_LEFT),
    ]);
    $this->assertDatabaseCount('staff_subjects', 3);
    $this->assertDatabaseHas('staff_subjects', [
        'term_id' => $data['term']->id,
        'user_id' => $staff->id,
        'school_class_id' => $data['classB']->id,
        'subject_id' => $data['english']->id,
    ]);
    $this->assertDatabaseHas('class_subjects', [
        'term_id' => $data['term']->id,
        'school_class_id' => $data['classB']->id,
        'subject_id' => $data['math']->id,
    ]);
    expect(AuditLog::where('event', 'staff.registered')->where('subject_id', $staff->id)->exists())->toBeTrue();
    Notification::assertSentTo($staff, QueuedVerifyEmail::class, fn (QueuedVerifyEmail $notification): bool => $notification->connection === 'sync');

    $staff->markEmailAsVerified();
    Volt::test('pages.auth.login')
        ->set('school_number', $data['school']->school_number)
        ->set('email', $staff->email)
        ->set('password', 'Password123!')
        ->call('login')
        ->assertHasNoErrors();
    $this->assertAuthenticatedAs($staff->fresh());
});

it('keeps the staff account when verification email delivery fails', function () {
    $data = staffRegistrationFixture();
    $dispatcher = Mockery::mock(Dispatcher::class);
    $dispatcher->shouldReceive('send')->once()->andThrow(new RuntimeException('SMTP unavailable'));
    app()->instance(Dispatcher::class, $dispatcher);

    completeStaffRegistration($data)
        ->set('email', 'mail.failure@example.test')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect(route('staff.index'));

    $this->assertDatabaseHas('users', [
        'school_id' => $data['school']->id,
        'email' => 'mail.failure@example.test',
    ]);
});

it('lets an administrator resend an unverified staff login link immediately', function () {
    Notification::fake();
    $data = staffRegistrationFixture();
    $staff = User::factory()->unverified()->create([
        'school_id' => $data['school']->id,
        'role' => 'teacher',
        'employment_status' => 'active',
    ]);

    Livewire::actingAs($data['admin'])->test(StaffManagement::class)
        ->call('resendVerification', $staff->id)
        ->assertHasNoErrors();

    Notification::assertSentTo($staff, QueuedVerifyEmail::class, fn (QueuedVerifyEmail $notification): bool => $notification->connection === 'sync');
});

it('rejects duplicate email within a school but permits it in another school', function () {
    Notification::fake();
    $first = staffRegistrationFixture();
    User::factory()->create(['school_id' => $first['school']->id, 'email' => 'shared@example.test']);

    Livewire::actingAs($first['admin'])->test(StaffRegister::class)
        ->set('name', 'Duplicate')
        ->set('email', ' SHARED@example.test ')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->call('next')
        ->assertHasErrors(['email']);

    $second = staffRegistrationFixture();
    completeStaffRegistration($second)
        ->set('email', 'shared@example.test')
        ->call('register')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', ['school_id' => $second['school']->id, 'email' => 'shared@example.test']);
});

it('rejects foreign designations, classes and subjects', function () {
    $data = staffRegistrationFixture();
    $foreign = staffRegistrationFixture();
    Livewire::actingAs($foreign['admin'])->test(StaffRegister::class);
    $foreignDesignation = Designation::where('school_id', $foreign['school']->id)->firstOrFail();

    Livewire::actingAs($data['admin'])->test(StaffRegister::class)
        ->set('name', 'Unsafe Staff')
        ->set('email', 'unsafe@example.test')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('job_title', 'Teacher')
        ->set('role', 'teacher')
        ->set('designation_id', (string) $foreignDesignation->id)
        ->set('base_salary', '1')
        ->call('register')
        ->assertHasErrors(['designation_id']);

    $ownDesignation = Designation::where('school_id', $data['school']->id)->where('name', 'Subject Teacher')->firstOrFail();
    completeStaffRegistration($data, $ownDesignation)
        ->set('email', 'foreign.mapping@example.test')
        ->set('is_class_teacher', false)
        ->set('teaching_assignments', [[
            'class_id' => (string) $foreign['classA']->id,
            'subject_ids' => [(string) $foreign['math']->id],
        ]])
        ->call('register')
        ->assertHasErrors(['teaching_assignments']);
});

it('does not replace an existing class teacher or leave a partial account', function () {
    $data = staffRegistrationFixture();
    $existing = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);
    $data['classA']->update(['class_teacher_user_id' => $existing->id]);

    completeStaffRegistration($data)
        ->set('email', 'conflict@example.test')
        ->call('register')
        ->assertHasErrors(['class_teacher_class_id']);

    $this->assertDatabaseMissing('users', ['school_id' => $data['school']->id, 'email' => 'conflict@example.test']);
    expect($data['classA']->fresh()->class_teacher_user_id)->toBe($existing->id);
});

it('requires a current term and at least one subject mapping for teaching staff', function () {
    $data = staffRegistrationFixture(withTerm: false);

    completeStaffRegistration($data)
        ->call('register')
        ->assertHasErrors(['teaching_assignments']);

    $this->assertDatabaseMissing('users', ['school_id' => $data['school']->id, 'email' => 'new.staff@example.test']);
});

it('requires confirmation before creating an administrator', function () {
    $data = staffRegistrationFixture();

    Livewire::actingAs($data['admin'])->test(StaffRegister::class)
        ->set('name', 'New Administrator')
        ->set('email', 'new.admin@example.test')
        ->set('password', 'Password123!')
        ->set('password_confirmation', 'Password123!')
        ->set('job_title', 'Administrator')
        ->set('role', 'admin')
        ->set('has_teaching_duties', false)
        ->set('base_salary', '1')
        ->call('register')
        ->assertHasErrors(['admin_confirmation']);
});

it('stores the same class and subject assignment independently in different terms', function () {
    $data = staffRegistrationFixture();
    $teacher = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);
    $secondTerm = Term::create(['school_id' => $data['school']->id, 'name' => 'Term 2', 'year' => 2026, 'is_current' => false, 'status' => 'planned', 'locked' => false]);

    foreach ([$data['term'], $secondTerm] as $term) {
        DB::table('staff_subjects')->insert([
            'school_id' => $data['school']->id,
            'term_id' => $term->id,
            'user_id' => $teacher->id,
            'school_class_id' => $data['classA']->id,
            'subject_id' => $data['math']->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    expect(DB::table('staff_subjects')->where('user_id', $teacher->id)->count())->toBe(2);
});

it('does not allow an unauthorized staff member to open registration', function () {
    $data = staffRegistrationFixture();
    $teacher = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);

    $this->actingAs($teacher)->get(route('staff.register'))->assertForbidden();
});
