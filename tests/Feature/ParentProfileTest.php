<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Parent Profile School', 'slug' => 'parent-profile-school']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->parent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    $this->learner = Student::create(['school_id' => $this->school->id, 'name' => 'Linked Profile Learner', 'admission_no' => 'P-001', 'status' => 'active']);
    $this->parent->portalStudents()->attach($this->learner, ['school_id' => $this->school->id, 'relationship' => 'Mother']);
});

it('opens a dedicated parent profile with linked learners and account sections', function () {
    $this->actingAs($this->admin)->get(route('parents.profile', $this->parent))->assertOk()
        ->assertViewIs('profiles.parent')->assertSee('Parent Profile')->assertSee('Linked Profile Learner')
        ->assertSee('Personal details')->assertSee('Portal &amp; account', false)->assertSee('Learner fee summary')
        ->assertSee('Mother')->assertSee('Save parent profile');
    $this->get(route('parents.index'))->assertOk()->assertSee('View profile');
});

it('preserves learner links and relationships when updating parent contact details', function () {
    $this->actingAs($this->admin)->patch(route('parents.profile.update', $this->parent), [
        'name' => 'Updated Parent', 'email' => $this->parent->email, 'phone' => '+256700123456',
    ])->assertSessionHasNoErrors();
    expect($this->parent->fresh()->name)->toBe('Updated Parent');
    $this->assertDatabaseHas('portal_user_students', ['user_id' => $this->parent->id, 'student_id' => $this->learner->id, 'relationship' => 'Mother']);
    $this->patch(route('parents.profile.update', $this->parent), [
        'name' => 'Updated Parent', 'email' => $this->parent->email, 'sync_learners' => '1', 'studentIds' => [$this->learner->id],
    ])->assertSessionHasNoErrors();
    $this->assertDatabaseHas('portal_user_students', ['user_id' => $this->parent->id, 'student_id' => $this->learner->id, 'relationship' => 'Mother']);
    $this->patch(route('parents.profile.update', $this->parent), [
        'name' => 'Updated Parent', 'email' => $this->parent->email, 'sync_learners' => '1',
    ])->assertSessionHasNoErrors();
    expect($this->parent->portalStudents()->count())->toBe(0);
});

it('stores parent photos and optional passwords without dropping learner links', function () {
    Storage::fake('public');
    $this->actingAs($this->admin)->patch(route('parents.profile.update', $this->parent), [
        'name' => $this->parent->name, 'email' => $this->parent->email,
        'password' => 'updated-parent-password', 'photo' => UploadedFile::fake()->image('parent.jpg'),
    ])->assertSessionHasNoErrors();
    $this->parent->refresh();
    Storage::disk('public')->assertExists($this->parent->avatar_path);
    expect(Hash::check('updated-parent-password', $this->parent->password))->toBeTrue();
    expect($this->parent->portalStudents()->count())->toBe(1);
    $learnerPhoto = UploadedFile::fake()->image('linked-learner.png')->store('students/'.$this->school->id, 'public');
    $this->learner->update(['photo_path' => $learnerPhoto]);
    $this->get(route('parents.profile', $this->parent))->assertOk()
        ->assertSee($this->parent->avatarUrl())->assertSee($this->learner->photoUrl())
        ->assertSee($this->parent->email)->assertSee($this->learner->name);
});

it('keeps finance data hidden without finance permission and rejects unauthorized access', function () {
    $designation = Designation::create(['school_id' => $this->school->id, 'name' => 'Parent Registrar', 'permissions' => ['parents.manage']]);
    $registrar = User::factory()->create(['school_id' => $this->school->id, 'role' => 'registrar', 'designation_id' => $designation->id]);
    $this->actingAs($registrar)->get(route('parents.profile', $this->parent))->assertOk()->assertDontSee('Learner fee summary')->assertDontSee('Recent posted payments')->assertViewHas('balances', fn ($rows) => $rows->isEmpty());
    $teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $this->actingAs($teacher)->get(route('parents.profile', $this->parent))->assertForbidden();
    $this->patch(route('parents.profile.update', $this->parent), ['name' => 'Disallowed'])->assertForbidden();
});

it('isolates parent profiles learner links and term filters by school', function () {
    $other = School::create(['name' => 'Other Parent School', 'slug' => 'other-parent-school']);
    $foreignParent = User::factory()->create(['school_id' => $other->id, 'role' => 'parent']);
    $foreignLearner = Student::create(['school_id' => $other->id, 'name' => 'Foreign Profile Learner', 'status' => 'active']);
    $foreignTerm = Term::create(['school_id' => $other->id, 'name' => 'Foreign Term', 'year' => 2026]);
    $this->parent->portalStudents()->attach($foreignLearner, ['school_id' => $other->id, 'relationship' => 'Parent']);
    $this->actingAs($this->admin)->get(route('parents.profile', $foreignParent))->assertNotFound();
    $this->get(route('parents.profile', $this->parent))->assertOk()->assertDontSee('Foreign Profile Learner');
    $this->get(route('parents.profile', [$this->parent, 'term' => $foreignTerm->id]))->assertNotFound();
    $this->patch(route('parents.profile.update', $this->parent), [
        'name' => $this->parent->name, 'email' => $this->parent->email, 'studentIds' => [$foreignLearner->id],
    ])->assertSessionHasErrors('studentIds.0');
    $this->get(route('parents.profile', $this->admin))->assertNotFound();
});
