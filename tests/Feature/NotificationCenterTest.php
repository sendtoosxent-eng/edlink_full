<?php

use App\Livewire\NotificationCenter;
use App\Models\School;
use App\Models\User;
use Database\Seeders\TeacherSubjectVisibilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows only notifications intended for the signed-in school user', function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);

    $school = School::where('school_number', 'EDL-TEACH')->firstOrFail();
    $parent = User::where('school_id', $school->id)->where('email', 'parent@edlink.local')->firstOrFail();
    $student = User::where('school_id', $school->id)->where('email', 'student@edlink.local')->firstOrFail();

    DB::table('school_notifications')->insert([
        'school_id' => $school->id,
        'user_id' => $student->id,
        'title' => 'Private student message',
        'message' => 'This must not be visible to the parent.',
        'type' => 'info',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($parent)
        ->get(route('notifications.index'))
        ->assertOk()
        ->assertSee('Welcome to the Edlink demo')
        ->assertDontSee('Private student message');
});

it('records notification read state separately for each user', function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);

    $school = School::where('school_number', 'EDL-TEACH')->firstOrFail();
    $parent = User::where('school_id', $school->id)->where('email', 'parent@edlink.local')->firstOrFail();
    $student = User::where('school_id', $school->id)->where('email', 'student@edlink.local')->firstOrFail();
    $notificationId = DB::table('school_notifications')->where('school_id', $school->id)->whereNull('user_id')->value('id');

    Livewire::actingAs($parent)->test(NotificationCenter::class)->call('markRead', $notificationId);

    $this->assertDatabaseHas('school_notification_reads', ['school_notification_id' => $notificationId, 'user_id' => $parent->id]);
    $this->assertDatabaseMissing('school_notification_reads', ['school_notification_id' => $notificationId, 'user_id' => $student->id]);
});

it('seeds a useful cross-module demo experience', function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);

    $schoolId = School::where('school_number', 'EDL-TEACH')->value('id');

    expect(DB::table('school_notifications')->where('school_id', $schoolId)->count())->toBeGreaterThanOrEqual(4)
        ->and(DB::table('attendance_records')->where('school_id', $schoolId)->count())->toBeGreaterThan(0)
        ->and(DB::table('exams')->where('school_id', $schoolId)->where('status', 'published')->exists())->toBeTrue()
        ->and(DB::table('student_houses')->where('school_id', $schoolId)->count())->toBe(2)
        ->and(DB::table('student_clubs')->where('school_id', $schoolId)->count())->toBe(2)
        ->and(DB::table('homework_assignments')->where('school_id', $schoolId)->exists())->toBeTrue()
        ->and(DB::table('school_events')->where('school_id', $schoolId)->count())->toBeGreaterThanOrEqual(2)
        ->and(DB::table('timetable_slots')->where('school_id', $schoolId)->count())->toBeGreaterThanOrEqual(3);
});
