<?php

use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('allows an assigned patron to view and export their house members', function () {
    $school = School::create(['name'=>'Activity School','slug'=>'activity-school']);
    $patron = User::factory()->create(['school_id'=>$school->id,'role'=>'teacher','employment_status'=>'active']);
    $class = SchoolClass::create(['school_id'=>$school->id,'name'=>'P6','education_stage'=>'primary','sort_order'=>1]);
    $student = Student::create(['school_id'=>$school->id,'school_class_id'=>$class->id,'name'=>'Amina Kato','admission_no'=>'ADM-100','gender'=>'female','status'=>'active']);
    $houseId = DB::table('student_houses')->insertGetId(['school_id'=>$school->id,'name'=>'Mandela House','color'=>'#facc15','patron_user_id'=>$patron->id,'created_at'=>now(),'updated_at'=>now()]);
    DB::table('student_house_memberships')->insert(['school_id'=>$school->id,'student_house_id'=>$houseId,'student_id'=>$student->id,'allocation_method'=>'automatic','assigned_by'=>$patron->id,'created_at'=>now(),'updated_at'=>now()]);

    $page = $this->actingAs($patron)->get(route('students.activities'));
    expect($page->status())->toBe(200);
    $page->assertSee('Mandela House')->assertSee('Amina Kato')->assertSee('Export for Excel');

    $export = $this->actingAs($patron)->get(route('students.activities.export',['type'=>'house','activity'=>$houseId]));
    $export->assertOk()->assertDownload()->assertHeader('content-type','text/csv; charset=UTF-8');
});

it('prevents an unrelated teacher from exporting another patron group', function () {
    $school = School::create(['name'=>'Protected Activity School','slug'=>'protected-activity-school']);
    $patron = User::factory()->create(['school_id'=>$school->id,'role'=>'teacher','employment_status'=>'active']);
    $otherTeacher = User::factory()->create(['school_id'=>$school->id,'role'=>'teacher','employment_status'=>'active']);
    $clubId = DB::table('student_clubs')->insertGetId(['school_id'=>$school->id,'name'=>'Debate Club','color'=>'#3b82f6','patron_user_id'=>$patron->id,'created_at'=>now(),'updated_at'=>now()]);

    $this->actingAs($otherTeacher)->get(route('students.activities.export',['type'=>'club','activity'=>$clubId]))->assertForbidden();
});
