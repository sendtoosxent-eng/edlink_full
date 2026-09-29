<?php

use App\Livewire\Reports;
use App\Models\{Designation, PlatformAdmin, School, SchoolGroup, Student, User};
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function branchSchool(array $attributes = []): School
{
    return School::create(array_merge(['name' => fake()->company(), 'slug' => fake()->unique()->slug()], $attributes));
}

it('lets an authorised director switch branches without changing their home school', function () {
    $group = SchoolGroup::create(['name' => 'Bright Future Schools', 'code' => 'BFS']);
    $kampala = branchSchool(['school_group_id' => $group->id, 'name' => 'Kampala School']);
    $entebbe = branchSchool(['school_group_id' => $group->id, 'name' => 'Entebbe School']);
    $director = User::factory()->create(['school_id' => $kampala->id, 'role' => 'admin']);
    $director->schoolAccesses()->syncWithoutDetaching([$entebbe->id => ['role' => 'admin', 'can_view_group' => true]]);

    $this->actingAs($director)->put(route('branch-context.update'), ['school_id' => $entebbe->id])
        ->assertRedirect(route('dashboard'))
        ->assertSessionHas('active_school_id', $entebbe->id);

    expect($director->fresh()->school_id)->toBe($kampala->id);
    $this->withSession(['active_school_id' => $entebbe->id])->get(route('dashboard'))
        ->assertOk()->assertSee('Entebbe School');
});

it('rejects switching to a school outside the users memberships', function () {
    $home = branchSchool();
    $other = branchSchool();
    $director = User::factory()->create(['school_id' => $home->id, 'role' => 'admin']);

    $this->actingAs($director)->put(route('branch-context.update'), ['school_id' => $other->id])->assertNotFound();
});

it('lets platform owners create groups and grant a director all branch access', function () {
    $platformAdmin = PlatformAdmin::create(['name' => 'Owner', 'email' => 'owner@edlink.test', 'password' => 'StrongPassword!123', 'role' => 'platform_owner', 'is_active' => true]);
    $first = branchSchool();
    $second = branchSchool();
    $director = User::factory()->create(['school_id' => $first->id, 'role' => 'admin', 'email' => 'director@example.test']);
    $session = ['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp];

    $this->actingAs($platformAdmin, 'platform')->withSession($session)->post(route('platform.groups.store'), [
        'name' => 'Bright Future Schools', 'code' => 'BFS', 'school_ids' => [$first->id, $second->id],
    ])->assertRedirect();

    $group = SchoolGroup::where('code', 'BFS')->firstOrFail();
    $this->actingAs($platformAdmin, 'platform')->withSession($session)->post(route('platform.groups.access.store', $group), [
        'email' => $director->email,
    ])->assertRedirect();

    $this->actingAs($director)->get(route('group-dashboard'))
        ->assertOk()
        ->assertSee('Read-only group reporting')
        ->assertSee($first->name)
        ->assertSee($second->name);

    Livewire::actingAs($director)->test(Reports::class)
        ->assertSet('schoolScope', (string) $first->id)
        ->set('schoolScope', 'all')
        ->assertSee('All authorised branches');

    expect(DB::table('school_user_access')->where('user_id', $director->id)->where('can_view_group', true)->count())->toBe(2);
});

it('generates admission numbers from the first letter of the branch', function () {
    $school = branchSchool(['name' => 'Central Academy', 'branch_name' => 'Kampala']);
    $generator = app(\App\Services\AdmissionNumberGenerator::class);

    expect($generator->generate($school))->toBe('K-0001');
    Student::create(['school_id' => $school->id, 'name' => 'First Learner', 'admission_no' => 'K-0001', 'status' => 'active']);
    expect($generator->generate($school))->toBe('K-0002');
});

it('maps one staff member to different roles in different branches', function () {
    $platformAdmin = PlatformAdmin::create(['name' => 'Owner', 'email' => 'roles@edlink.test', 'password' => 'StrongPassword!123', 'role' => 'platform_owner', 'is_active' => true]);
    $group = SchoolGroup::create(['name' => 'Role Test Group', 'code' => 'RTG']);
    $first = branchSchool(['school_group_id' => $group->id, 'name' => 'First Branch']);
    $second = branchSchool(['school_group_id' => $group->id, 'name' => 'Second Branch']);
    $staff = User::factory()->create(['school_id' => $first->id, 'role' => 'teacher', 'email' => 'shared.staff@example.test']);
    $teacherDesignation = Designation::create(['school_id' => $first->id, 'name' => 'Teacher', 'permissions' => ['academics']]);
    $session = ['platform_mfa_passed' => true, 'platform_last_activity' => now()->timestamp];

    $this->actingAs($platformAdmin, 'platform')->withSession($session)->post(route('platform.groups.access.store', $group), [
        'email' => $staff->email, 'school_id' => $first->id, 'role' => 'teacher', 'designation_id' => $teacherDesignation->id,
    ])->assertRedirect();
    $this->actingAs($platformAdmin, 'platform')->withSession($session)->post(route('platform.groups.access.store', $group), [
        'email' => $staff->email, 'school_id' => $second->id, 'role' => 'bursar',
    ])->assertRedirect();

    $this->assertDatabaseHas('school_user_access', ['user_id' => $staff->id, 'school_id' => $first->id, 'role' => 'teacher', 'designation_id' => $teacherDesignation->id]);
    $this->assertDatabaseHas('school_user_access', ['user_id' => $staff->id, 'school_id' => $second->id, 'role' => 'bursar', 'designation_id' => null]);
});
