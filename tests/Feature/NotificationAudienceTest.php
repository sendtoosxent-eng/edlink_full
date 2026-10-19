<?php

use App\Livewire\Attendance;
use App\Livewire\NotificationCenter;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentGuardian;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Notification School', 'slug' => 'notification-school', 'license_status' => 'active', 'license_expires_at' => now()->addYear()]);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->parent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    $this->learner = User::factory()->create(['school_id' => $this->school->id, 'role' => 'student']);
    $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary One']);
    $this->child = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'name' => 'Family Learner', 'status' => 'active']);
    foreach ([$this->parent, $this->learner] as $user) {
        $user->portalStudents()->attach($this->child->id, ['school_id' => $this->school->id, 'relationship' => $user->role]);
    }
});

it('hides existing system alerts from portal accounts and their notification bells', function () {
    foreach (['Edlink system health alert', 'School sports day'] as $title) {
        DB::table('school_notifications')->insert(['school_id' => $this->school->id, 'title' => $title, 'message' => $title, 'type' => 'info', 'created_at' => now(), 'updated_at' => now()]);
    }
    foreach ([$this->parent, $this->learner] as $user) {
        $this->actingAs($user)->get(route('portal.home'))->assertOk()->assertSee('School sports day')->assertDontSee('Edlink system health alert');
        $this->get(route('notifications.index'))->assertOk()->assertSee('School sports day')->assertDontSee('Edlink system health alert');
        Livewire::actingAs($user)->test(NotificationCenter::class)->call('markAllRead');
        $healthId = DB::table('school_notifications')->where('title', 'Edlink system health alert')->value('id');
        $this->assertDatabaseMissing('school_notification_reads', ['user_id' => $user->id, 'school_notification_id' => $healthId]);
    }
    $this->actingAs($this->admin)->get(route('notifications.index'))->assertOk()->assertSee('Edlink system health alert');
});

it('also excludes internal alerts from mobile notifications and unread counts', function () {
    DB::table('school_notifications')->insert(['school_id' => $this->school->id, 'title' => 'Edlink system health alert', 'message' => 'Internal backup issue', 'type' => 'warning', 'created_at' => now(), 'updated_at' => now()]);
    Sanctum::actingAs($this->learner, ['mobile']);
    $this->getJson('/api/v1/notifications')->assertOk()->assertJsonPath('meta.unread_count', 0)->assertJsonCount(0, 'data.data');
    $this->postJson('/api/v1/notifications/read')->assertOk()->assertJsonPath('data.read', 0);
});

it('notifies portal-linked and guardian-linked parents when attendance is saved and corrected', function () {
    Term::create(['school_id' => $this->school->id, 'name' => 'Term One', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $guardianParent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    StudentGuardian::create(['student_id' => $this->child->id, 'name' => $guardianParent->name, 'email' => $guardianParent->email, 'relationship' => 'Mother']);
    StudentGuardian::create(['student_id' => $this->child->id, 'name' => $this->parent->name, 'email' => $this->parent->email, 'relationship' => 'Father']);
    $unrelated = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent']);
    $component = Livewire::actingAs($this->admin)->test(Attendance::class)->set('schoolClassId', (string) $this->class->id);
    foreach (['present', 'absent'] as $status) {
        $this->actingAs($this->admin);
        $component->set('statuses.'.$this->child->id, $status)->call('save')->assertHasNoErrors();
        foreach ([$this->parent, $guardianParent] as $parent) {
            $this->assertDatabaseHas('school_notifications', ['user_id' => $parent->id, 'student_id' => $this->child->id, 'category' => 'daily_attendance', 'title' => 'Daily attendance: '.ucfirst($status)]);
            $this->actingAs($parent)->get(route('notifications.index'))->assertOk()->assertSee('was marked '.$status);
        }
        $this->assertDatabaseMissing('school_notifications', ['user_id' => $unrelated->id]);
    }
    expect(DB::table('school_notifications')->where('user_id', $this->parent->id)->count())->toBe(1);
});

it('creates new health alerts only for administrators', function () {
    config(['edlink.monitoring.alert_webhook_url' => null]);
    $this->artisan('edlink:monitor')->assertExitCode(1);
    $this->assertDatabaseHas('school_notifications', ['user_id' => $this->admin->id, 'category' => 'system_health']);
    expect(DB::table('school_notifications')->where('category', 'system_health')->whereNull('user_id')->exists())->toBeFalse();
    $this->assertDatabaseMissing('school_notifications', ['user_id' => $this->parent->id, 'category' => 'system_health']);
    $this->assertDatabaseMissing('school_notifications', ['user_id' => $this->learner->id, 'category' => 'system_health']);
});
