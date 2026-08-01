<?php

use App\Models\Designation;
use App\Models\School;
use App\Models\User;
use App\Models\SchoolClass;
use App\Models\Term;
use App\Models\Subject;
use App\Support\DesignationPermissions;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function teacherWorkspaceUser(array $extraPermissions = []): User
{
    $school = School::create(['name' => 'Teacher Workspace School', 'slug' => 'teacher-workspace-school-'.uniqid()]);
    $designation = Designation::create([
        'school_id' => $school->id,
        'name' => 'Subject Teacher',
        'permissions' => array_values(array_unique([...DesignationPermissions::defaults()['Subject Teacher'], ...$extraPermissions])),
    ]);

    return User::factory()->create([
        'school_id' => $school->id,
        'designation_id' => $designation->id,
        'role' => 'teacher',
        'employment_status' => 'active',
    ]);
}

it('shows a teacher-focused dashboard with teaching cards and charts', function () {
    $teacher = teacherWorkspaceUser();

    $this->actingAs($teacher)->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('Teacher Dashboard')
        ->assertSee('Assigned subjects')
        ->assertSee('Assigned classes')
        ->assertSee('Lessons today')
        ->assertSee('Pending mark sheets')
        ->assertSee('Attendance activity')
        ->assertSee('Assigned-subject performance')
        ->assertDontSee('Finance Dashboard');
});

it('shows extra sidebar modules when a teacher designation receives extra rights', function () {
    $teacher = teacherWorkspaceUser(['finance.expenses']);

    $this->actingAs($teacher)->get(route('workbench.home'))
        ->assertOk()
        ->assertSee('Teacher Dashboard')
        ->assertSee('Finance')
        ->assertSee('Expenses');
});

it('shows a teacher their next lesson and countdown card', function () {
    $this->travelTo(now()->startOfDay()->addHours(8));
    $teacher = teacherWorkspaceUser();
    $term = Term::create(['school_id'=>$teacher->school_id,'name'=>'Term 1','year'=>now()->year,'is_current'=>true,'status'=>'open','locked'=>false]);
    $class = SchoolClass::create(['school_id'=>$teacher->school_id,'name'=>'S3','education_stage'=>'lower_secondary','sort_order'=>1]);
    $subject = Subject::create(['school_id'=>$teacher->school_id,'name'=>'Biology','code'=>'BIO']);
    DB::table('timetable_slots')->insert(['school_id'=>$teacher->school_id,'term_id'=>$term->id,'school_class_id'=>$class->id,'subject_id'=>$subject->id,'user_id'=>$teacher->id,'day_of_week'=>now()->format('l'),'starts_at'=>now()->addMinutes(20)->format('H:i'),'ends_at'=>now()->addHour()->format('H:i'),'created_at'=>now(),'updated_at'=>now()]);

    $this->actingAs($teacher)->get(route('workbench.home'))
        ->assertOk()->assertSee('Your next lesson')->assertSee('Biology')->assertSee('S3')->assertSee('Starts in');
    $this->travelBack();
});
