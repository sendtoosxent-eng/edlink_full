<?php

use App\Livewire\Reports;
use App\Models\AttendanceRecord;
use App\Models\FeePayment;
use App\Models\ReportExport;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('filters learner and attendance reports by class', function () {
    $school = School::create(['name' => 'Class Filter School', 'slug' => 'class-filter-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Term 1', 'year' => now()->year, 'term_number' => 1, 'is_current' => true, 'status' => 'open']);
    $classA = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary One', 'sort_order' => 1]);
    $classB = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary Two', 'sort_order' => 2]);
    $learnerA = Student::create(['school_id' => $school->id, 'school_class_id' => $classA->id, 'term_id' => $term->id, 'name' => 'Amina Primary One', 'admission_no' => 'P1-001', 'status' => 'active']);
    $learnerB = Student::create(['school_id' => $school->id, 'school_class_id' => $classB->id, 'term_id' => $term->id, 'name' => 'Brian Primary Two', 'admission_no' => 'P2-001', 'status' => 'active']);
    AttendanceRecord::create(['school_id' => $school->id, 'term_id' => $term->id, 'student_id' => $learnerA->id, 'school_class_id' => $classA->id, 'attendance_date' => today(), 'status' => 'present', 'recorded_by' => $admin->id]);
    AttendanceRecord::create(['school_id' => $school->id, 'term_id' => $term->id, 'student_id' => $learnerB->id, 'school_class_id' => $classB->id, 'attendance_date' => today(), 'status' => 'absent', 'recorded_by' => $admin->id]);
    foreach ([[$learnerA, 100], [$learnerB, 250]] as [$learner, $amount]) {
        $payment = FeePayment::create([
            'school_id' => $school->id,
            'student_id' => $learner->id,
            'term_id' => $term->id,
            'amount' => $amount,
            'method' => 'cash',
            'paid_at' => now(),
            'recorded_by' => $admin->id,
        ]);
        $payment->ledgerEntry()->update(['status' => 'posted']);
    }

    $component = Livewire::actingAs($admin)->test(Reports::class)
        ->assertSee('All classes')
        ->assertSee('Primary One')
        ->assertSee('Primary Two')
        ->set('classId', (string) $classA->id);

    expect($component->viewData('result')['rows']->pluck(1)->all())
        ->toBe(['Amina Primary One']);

    $component->set('report', 'attendance');
    expect($component->viewData('result')['rows']->pluck(1)->all())
        ->toBe(['Amina Primary One']);

    $component->set('report', 'fee_collection');
    $feeResult = $component->viewData('result');
    expect($feeResult['rows']->pluck(2)->all())->toBe(['Amina Primary One'])
        ->and((float) $feeResult['summaryValue'])->toBe(100.0);
});

it('stores the class filter in exports and rejects classes from another school', function () {
    Queue::fake();
    $school = School::create(['name' => 'Export Filter School', 'slug' => 'export-filter-school']);
    $otherSchool = School::create(['name' => 'Foreign School', 'slug' => 'foreign-filter-school']);
    $admin = User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Senior One']);
    $foreignClass = SchoolClass::create(['school_id' => $otherSchool->id, 'name' => 'Foreign Class']);

    Livewire::actingAs($admin)->test(Reports::class)
        ->set('classId', (string) $class->id)
        ->call('queueExport');

    expect(ReportExport::firstOrFail()->filters['classId'])->toBe((string) $class->id);

    Livewire::actingAs($admin)->test(Reports::class)
        ->set('classId', (string) $foreignClass->id)
        ->assertSet('classId', '');
});
