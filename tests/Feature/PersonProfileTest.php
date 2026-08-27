<?php

use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('shows and updates a branch-owned student profile photograph', function () {
    Storage::fake('public');
    $school = School::create(['name' => 'Profile School', 'slug' => 'profile-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $student = Student::create(['school_id' => $school->id, 'name' => 'Profile Learner', 'admission_no' => 'PS-001', 'status' => 'active']);

    $this->actingAs($admin)->get(route('students.profile', $student))
        ->assertOk()->assertSee('Student Profile')->assertSee('Profile Learner');

    $this->patch(route('students.profile.update', $student), [
        'name' => 'Profile Learner', 'admission_no' => 'PS-001',
        'photo' => UploadedFile::fake()->image('learner.jpg', 600, 800),
    ])->assertSessionHasNoErrors();

    $student->refresh();
    Storage::disk('public')->assertExists($student->photo_path);
    $this->get($student->photoUrl())->assertOk();
});

it('keeps profile screens isolated by branch and role', function () {
    $school = School::create(['name' => 'Home Profile School', 'slug' => 'home-profile-school']);
    $other = School::create(['name' => 'Other Profile School', 'slug' => 'other-profile-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $foreignStudent = Student::create(['school_id' => $other->id, 'name' => 'Foreign Learner', 'status' => 'active']);
    $parent = User::factory()->create(['school_id' => $school->id, 'role' => 'parent']);

    $this->actingAs($admin)->get(route('students.profile', $foreignStudent))->assertNotFound();
    $this->get(route('parents.profile', $parent))->assertOk()->assertSee('Parent Profile');
    $this->get(route('staff.profile', $parent))->assertNotFound();
});

it('serves a saved school badge without relying on the public storage link', function () {
    Storage::fake('public');
    $path = UploadedFile::fake()->image('badge.png', 300, 300)->store('school-badges', 'public');
    $school = School::create(['name' => 'Badge School', 'slug' => 'badge-school', 'badge_path' => $path]);

    expect($school->badgeUrl())->not->toBeNull();
    $this->get($school->badgeUrl())->assertOk();
});
