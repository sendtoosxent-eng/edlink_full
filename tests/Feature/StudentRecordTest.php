<?php

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\StudentCategory;
use App\Models\StudentEnrolment;
use App\Models\StudentGuardian;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Student Record School', 'slug' => 'student-record-school']);
    $this->admin = User::factory()->create(['school_id' => $this->school->id, 'role' => 'admin']);
    $this->term = Term::create(['school_id' => $this->school->id, 'name' => 'Term One', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $this->class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary Five', 'education_stage' => 'primary']);
    $this->category = StudentCategory::create(['school_id' => $this->school->id, 'name' => 'Day scholar']);
    $this->student = Student::create(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'student_category_id' => $this->category->id, 'term_id' => $this->term->id, 'name' => 'Complete Record Learner', 'admission_no' => 'REC-001', 'status' => 'active', 'medical_notes' => 'Private medical record']);
    StudentEnrolment::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'student_category_id' => $this->category->id, 'base_fee_amount' => 900000, 'status' => 'active', 'enrolled_at' => today()]);
    $parent = User::factory()->create(['school_id' => $this->school->id, 'name' => 'Family Record Parent', 'role' => 'parent']);
    StudentGuardian::create(['student_id' => $this->student->id, 'name' => $parent->name, 'email' => $parent->email, 'relationship' => 'Mother']);
    foreach (['present', 'absent'] as $index => $status) AttendanceRecord::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'school_class_id' => $this->class->id, 'term_id' => $this->term->id, 'attendance_date' => today()->subDays($index), 'session_key' => 'daily', 'status' => $status]);
    foreach (['Mathematics', 'English'] as $index => $name) {
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => $name, 'code' => 'RS'.$index]);
        DB::table('class_subjects')->insert(['school_id' => $this->school->id, 'school_class_id' => $this->class->id, 'term_id' => $this->term->id, 'subject_id' => $subject->id]);
        $exam = Exam::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $this->class->id, 'name' => 'Progress '.$name, 'published_at' => now()]);
        $paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100]);
        DB::table('exam_marks')->insert(['exam_paper_id' => $paper->id, 'student_id' => $this->student->id, 'score' => 80]);
        DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $paper->id, 'status' => 'approved']);
    }
    $payment = FeePayment::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'term_id' => $this->term->id, 'amount' => 250000, 'method' => 'cash', 'transaction_id' => 'POSTED-RECORD', 'paid_at' => now()]);
    $payment->ledgerEntry()->update(['status' => 'posted']);
    FeePayment::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'term_id' => $this->term->id, 'amount' => 100000, 'method' => 'cash', 'transaction_id' => 'PENDING-RECORD', 'paid_at' => now()]);
});

it('brings the learner record into one prefilled screen with correct posted totals', function () {
    $this->actingAs($this->admin)->get(route('students.profile', $this->student))->assertOk()
        ->assertSee('value="Complete Record Learner"', false)->assertSee('Parents & guardians')->assertSee('Family Record Parent')
        ->assertSee('Mathematics')->assertSee('English')->assertSee('Progress Mathematics')->assertSee('80.0%')
        ->assertSee('50%')->assertSee('900,000.00')->assertSee('250,000.00')->assertSee('650,000.00')
        ->assertSee('POSTED-RECORD')->assertDontSee('PENDING-RECORD')->assertSee('Private medical record')
        ->assertSee('Editable')->assertSee('Read only')->assertViewHas('canEdit', true);
});

it('lets assigned teachers view the record but preserves editing finance and subject restrictions', function () {
    $teacher = User::factory()->create(['school_id' => $this->school->id, 'role' => 'teacher']);
    $math = Subject::where('name', 'Mathematics')->firstOrFail();
    DB::table('staff_subjects')->insert(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'user_id' => $teacher->id, 'subject_id' => $math->id, 'school_class_id' => $this->class->id]);
    $this->actingAs($teacher)->get(route('students.profile', $this->student))->assertOk()
        ->assertViewHas('canEdit', false)->assertSee('Progress Mathematics')->assertDontSee('Progress English')
        ->assertDontSee('POSTED-RECORD')->assertDontSee('Private medical record')->assertDontSee('Save student details');
    $this->patch(route('students.profile.update', $this->student), ['name' => 'Not allowed'])->assertForbidden();
    $otherClass = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Another class']);
    $unassigned = Student::create(['school_id' => $this->school->id, 'school_class_id' => $otherClass->id, 'name' => 'Unassigned learner', 'status' => 'active']);
    $this->get(route('students.profile', $unassigned))->assertNotFound();
    $parent = User::where('role', 'parent')->firstOrFail();
    $this->actingAs($parent)->get(route('students.profile', $this->student))->assertForbidden();
});

it('keeps placement and derived records read-only even if submitted in a profile edit', function () {
    $this->actingAs($this->admin)->patch(route('students.profile.update', $this->student), [
        'name' => 'Corrected Name', 'admission_date' => '2026-01-15', 'status' => 'inactive', 'school_class_id' => null, 'base_fee_amount' => 1,
    ])->assertSessionHasNoErrors();
    $student = $this->student->fresh();
    expect($student->name)->toBe('Corrected Name')->and($student->admission_date->toDateString())->toBe('2026-01-15')
        ->and($student->status)->toBe('active')->and($student->school_class_id)->toBe($this->class->id)
        ->and((float) $student->enrolments()->first()->base_fee_amount)->toBe(900000.0);
});

it('uses the selected historical term without changing the current placement', function () {
    $old = Term::create(['school_id' => $this->school->id, 'name' => 'Old Term', 'year' => 2025, 'status' => 'closed']);
    $oldClass = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Primary Four']);
    StudentEnrolment::create(['school_id' => $this->school->id, 'student_id' => $this->student->id, 'term_id' => $old->id, 'school_class_id' => $oldClass->id, 'student_category_id' => $this->category->id, 'base_fee_amount' => 700000, 'status' => 'active', 'enrolled_at' => '2025-01-15']);
    $this->actingAs($this->admin)->get(route('students.profile', [$this->student, 'term' => $old->id]))->assertOk()
        ->assertViewHas('placementClass', fn ($class) => $class->id === $oldClass->id)
        ->assertSee('700,000.00')->assertDontSee('POSTED-RECORD')->assertDontSee('Progress Mathematics');
    $otherSchool = School::create(['name' => 'Foreign School', 'slug' => 'foreign-record-school']);
    $foreignTerm = Term::create(['school_id' => $otherSchool->id, 'name' => 'Foreign term', 'year' => 2026]);
    $this->get(route('students.profile', [$this->student, 'term' => $foreignTerm->id]))->assertNotFound();
});
