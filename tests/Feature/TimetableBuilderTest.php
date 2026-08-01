<?php

use App\Livewire\Timetable;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('renders the timetable builder and creates a school-scoped slot', function () {
    $school = School::create(['name'=>'Timetable School','slug'=>'timetable-school','status'=>'active','license_status'=>'active']);
    $admin = User::factory()->create(['school_id'=>$school->id,'role'=>'admin','employment_status'=>'active']);
    $teacher = User::factory()->create(['school_id'=>$school->id,'role'=>'teacher','employment_status'=>'active']);
    $term = Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open','locked'=>false]);
    $class = SchoolClass::create(['school_id'=>$school->id,'name'=>'S1','education_stage'=>'lower_secondary','sort_order'=>1]);
    $subject = Subject::create(['school_id'=>$school->id,'name'=>'Mathematics','code'=>'MATH']);

    $this->actingAs($admin)->get(route('timetable.index'))
        ->assertOk()->assertSee('Create the School Timetable')->assertSee('webfav.png');

    Livewire::actingAs($admin)->test(Timetable::class)
        ->set('classId',(string)$class->id)->set('day','Monday')->set('startsAt','08:00')->set('endsAt','08:40')
        ->set('subjectId',(string)$subject->id)->set('teacherId',(string)$teacher->id)->call('saveSlot')
        ->assertHasNoErrors()->assertSee('Mathematics');

    expect(DB::table('timetable_slots')->where(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$class->id,'subject_id'=>$subject->id,'user_id'=>$teacher->id])->exists())->toBeTrue();
});

it('rejects overlapping timetable slots for the same class', function () {
    $school = School::create(['name'=>'Conflict School','slug'=>'conflict-school','status'=>'active','license_status'=>'active']);
    $admin = User::factory()->create(['school_id'=>$school->id,'role'=>'admin','employment_status'=>'active']);
    $term = Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open','locked'=>false]);
    $class = SchoolClass::create(['school_id'=>$school->id,'name'=>'P7','education_stage'=>'primary','sort_order'=>1]);
    DB::table('timetable_slots')->insert(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$class->id,'day_of_week'=>'Tuesday','starts_at'=>'09:00','ends_at'=>'10:00','label'=>'Assembly','created_at'=>now(),'updated_at'=>now()]);

    Livewire::actingAs($admin)->test(Timetable::class)->set('classId',(string)$class->id)->set('day','Tuesday')->set('startsAt','09:30')->set('endsAt','10:30')->set('label','Break')->call('saveSlot')->assertHasErrors(['startsAt']);
});

it('shows the existing lesson details when a teacher has a timetable conflict', function () {
    $school = School::create(['name'=>'Teacher Conflict School','slug'=>'teacher-conflict-school','status'=>'active','license_status'=>'active']);
    $admin = User::factory()->create(['school_id'=>$school->id,'role'=>'admin','employment_status'=>'active']);
    $teacher = User::factory()->create(['school_id'=>$school->id,'role'=>'teacher','employment_status'=>'active']);
    $term = Term::create(['school_id'=>$school->id,'name'=>'Term 1','year'=>2026,'is_current'=>true,'status'=>'open','locked'=>false]);
    $firstClass = SchoolClass::create(['school_id'=>$school->id,'name'=>'S1','education_stage'=>'lower_secondary','sort_order'=>1]);
    $secondClass = SchoolClass::create(['school_id'=>$school->id,'name'=>'S2','education_stage'=>'lower_secondary','sort_order'=>2]);
    $subject = Subject::create(['school_id'=>$school->id,'name'=>'Mathematics','code'=>'MATH']);
    DB::table('timetable_slots')->insert(['school_id'=>$school->id,'term_id'=>$term->id,'school_class_id'=>$firstClass->id,'subject_id'=>$subject->id,'user_id'=>$teacher->id,'day_of_week'=>'Wednesday','starts_at'=>'10:00','ends_at'=>'10:40','created_at'=>now(),'updated_at'=>now()]);

    Livewire::actingAs($admin)->test(Timetable::class)
        ->set('classId',(string)$secondClass->id)->set('day','Wednesday')->set('startsAt','10:20')->set('endsAt','11:00')
        ->set('subjectId',(string)$subject->id)->set('teacherId',(string)$teacher->id)->call('saveSlot')
        ->assertHasErrors(['teacherId'])
        ->assertSee('Mathematics with S1')
        ->assertSee('10:00');
});
