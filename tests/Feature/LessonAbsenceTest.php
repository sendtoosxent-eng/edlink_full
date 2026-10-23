<?php

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use App\Support\NotificationVisibility;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function lessonAbsenceFixture(): array
{
    $school = School::create(['name' => 'Lesson School', 'slug' => 'lesson-school', 'license_status' => 'active', 'license_expires_at' => now()->addYear()]);
    $teacher = User::factory()->create(['school_id' => $school->id, 'role' => 'teacher']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $academic = User::factory()->create(['school_id' => $school->id, 'role' => 'academic_admin']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 3', 'year' => 2026, 'status' => 'open', 'is_current' => true]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Senior 2']);
    $subject = Subject::create(['school_id' => $school->id, 'name' => 'Mathematics', 'code' => 'MAT']);
    $slot = DB::table('timetable_slots')->insertGetId([
        'school_id' => $school->id, 'term_id' => $term->id, 'school_class_id' => $class->id,
        'subject_id' => $subject->id, 'user_id' => $teacher->id, 'day_of_week' => now()->format('l'),
        'starts_at' => '10:00:00', 'ends_at' => '11:00:00', 'created_at' => now(), 'updated_at' => now(),
    ]);

    return compact('school', 'teacher', 'admin', 'academic', 'term', 'slot');
}

beforeEach(function () {
    $this->travelTo(\Carbon\Carbon::parse('2026-10-23 09:00:00'));
});

it('reports a lesson once and privately notifies only active school administrators', function () {
    $data = lessonAbsenceFixture();
    $parent = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'parent']);
    User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);
    User::factory()->create(['school_id' => $data['school']->id, 'role' => 'admin', 'employment_status' => 'inactive']);
    $other = School::create(['name' => 'Other', 'slug' => 'other-lesson-school']);
    User::factory()->create(['school_id' => $other->id, 'role' => 'admin']);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $url = '/api/v1/teacher/lessons/'.$data['slot'].'/absence';
    $report = $this->postJson($url)->assertOk()->assertJsonStructure(['data' => ['id', 'reported_at', 'message']])->json('data.id');
    $this->postJson($url)->assertOk()->assertJsonPath('data.id', $report);
    expect(DB::table('lesson_absence_reports')->count())->toBe(1)
        ->and(DB::table('school_notifications')->where('category', 'lesson_absence')->count())->toBe(2)
        ->and(NotificationVisibility::forUser($parent)->count())->toBe(0);
    foreach ([$data['admin'], $data['academic']] as $admin) {
        $notice = NotificationVisibility::forUser($admin)->first();
        expect($notice->message)->toContain($data['teacher']->name, 'Mathematics', 'Senior 2', '23 Oct 2026', '10:00', '11:00');
    }
    $this->assertDatabaseHas('audit_logs', ['event' => 'lesson.absence.reported', 'user_id' => $data['teacher']->id]);
    $this->getJson('/api/v1/dashboard')->assertOk()->assertJsonPath('data.today_timetable.0.id', $data['slot'])
        ->assertJsonPath('data.today_timetable.0.absence_reported_at', now()->toDateTimeString());
});

it('rejects lessons belonging to another teacher or another school', function () {
    $data = lessonAbsenceFixture();
    $otherTeacher = User::factory()->create(['school_id' => $data['school']->id, 'role' => 'teacher']);
    Sanctum::actingAs($otherTeacher, ['mobile']);
    $this->postJson('/api/v1/teacher/lessons/'.$data['slot'].'/absence')->assertNotFound();
    $otherSchool = School::create(['name' => 'Foreign', 'slug' => 'foreign-absence', 'license_status' => 'active', 'license_expires_at' => now()->addYear()]);
    Sanctum::actingAs(User::factory()->create(['school_id' => $otherSchool->id, 'role' => 'teacher']), ['mobile']);
    $this->postJson('/api/v1/teacher/lessons/'.$data['slot'].'/absence')->assertUnprocessable();
    expect(DB::table('lesson_absence_reports')->count())->toBe(0);
});

it('rejects ended lessons wrong days and closed terms', function () {
    $data = lessonAbsenceFixture();
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $url = '/api/v1/teacher/lessons/'.$data['slot'].'/absence';
    DB::table('timetable_slots')->where('id', $data['slot'])->update(['ends_at' => '08:30:00']);
    $this->postJson($url)->assertUnprocessable();
    DB::table('timetable_slots')->where('id', $data['slot'])->update(['ends_at' => '11:00:00', 'day_of_week' => 'Monday']);
    $this->postJson($url)->assertUnprocessable();
    $data['term']->update(['status' => 'closed']);
    $this->postJson($url)->assertUnprocessable();
    expect(DB::table('lesson_absence_reports')->count())->toBe(0);
});

it('requires an authenticated teacher and available admin recipients', function () {
    $data = lessonAbsenceFixture();
    $url = '/api/v1/teacher/lessons/'.$data['slot'].'/absence';
    $this->postJson($url)->assertUnauthorized();
    $data['teacher']->update(['role' => 'student']);
    Sanctum::actingAs($data['teacher'], ['mobile']);
    $this->postJson($url)->assertForbidden();
    $data['teacher']->update(['role' => 'teacher']);
    Sanctum::actingAs($data['teacher']->fresh(), ['mobile']);
    $data['admin']->update(['employment_status' => 'inactive']);
    $data['academic']->update(['employment_status' => 'inactive']);
    $this->postJson($url)->assertUnprocessable();
    expect(DB::table('lesson_absence_reports')->count())->toBe(0)
        ->and(DB::table('school_notifications')->count())->toBe(0);
});
