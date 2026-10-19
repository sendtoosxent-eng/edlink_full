<?php

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Family School', 'slug' => 'family-school']);
    $this->parent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent', 'employment_status' => 'active']);
    $this->term = Term::create(['school_id' => $this->school->id, 'name' => 'Term One', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $this->children = collect(['Alice', 'Brian'])->map(function ($name, $index) {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class '.$name]);
        $child = Student::create(['school_id' => $this->school->id, 'school_class_id' => $class->id, 'name' => $name, 'admission_no' => 'FAM-'.$index, 'status' => 'active']);
        $this->parent->portalStudents()->attach($child->id, ['school_id' => $this->school->id, 'relationship' => 'Parent']);
        AttendanceRecord::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'student_id' => $child->id, 'attendance_date' => today(), 'status' => $index ? 'absent' : 'present']);
        Exam::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $class->id, 'name' => $name.' report', 'published_at' => now()]);
        $payment = FeePayment::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'student_id' => $child->id, 'amount' => ($index + 1) * 10000, 'method' => 'cash', 'transaction_id' => $name.' receipt', 'paid_at' => now()]);
        $payment->ledgerEntry()->update(['status' => 'posted']);
        return $child;
    });
});

it('reloads the selected childs payments results attendance and fee statistics in both directions', function () {
    $this->actingAs($this->parent);
    foreach ([1, 0, 1] as $index) {
        $child = $this->children[$index];
        $other = $this->children[1 - $index];
        $response = $this->get(route('portal.home', ['student' => $child->id]));
        $response->assertOk()
            ->assertSee('Payments recorded for '.$child->name.' this term.')
            ->assertSee($child->name.' receipt')->assertDontSee($other->name.' receipt')
            ->assertSee($child->name.' report')->assertDontSee($other->name.' report')
            ->assertSee($index ? '0%' : '100%')
            ->assertSee('UGX '.number_format(($index + 1) * 10000))
            ->assertSee('href="#performance"', false);
    }
});

it('does not allow a parent to select an unrelated learner', function () {
    $stranger = Student::create(['school_id' => $this->school->id, 'name' => 'Unrelated Child', 'status' => 'active']);
    $this->actingAs($this->parent)->get(route('portal.home', ['student' => $stranger->id]))
        ->assertOk()->assertDontSee('Unrelated Child')->assertSee('Alice receipt');
});
