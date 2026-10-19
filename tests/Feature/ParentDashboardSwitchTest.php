<?php

use App\Models\AttendanceRecord;
use App\Models\Exam;
use App\Models\ExamPaper;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Term;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->school = School::create(['name' => 'Family School', 'slug' => 'family-school', 'school_type' => 'primary']);
    $this->parent = User::factory()->create(['school_id' => $this->school->id, 'role' => 'parent', 'employment_status' => 'active']);
    $this->term = Term::create(['school_id' => $this->school->id, 'name' => 'Term One', 'year' => 2026, 'is_current' => true, 'status' => 'open']);
    $this->children = collect(['Alice', 'Brian'])->map(function ($name, $index) {
        $class = SchoolClass::create(['school_id' => $this->school->id, 'name' => 'Class '.$name, 'education_stage' => 'primary']);
        $child = Student::create(['school_id' => $this->school->id, 'school_class_id' => $class->id, 'term_id' => $this->term->id, 'name' => $name, 'admission_no' => 'FAM-'.$index, 'status' => 'active']);
        $this->parent->portalStudents()->attach($child->id, ['school_id' => $this->school->id, 'relationship' => 'Parent']);
        AttendanceRecord::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'student_id' => $child->id, 'attendance_date' => today(), 'status' => $index ? 'absent' : 'present']);
        $exam = Exam::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'school_class_id' => $class->id, 'name' => $name.' report', 'published_at' => now()]);
        $subject = Subject::create(['school_id' => $this->school->id, 'name' => 'Maths '.$name, 'code' => 'M'.$index]);
        $paper = ExamPaper::create(['exam_id' => $exam->id, 'subject_id' => $subject->id, 'maximum_score' => 100]);
        DB::table('exam_marks')->insert(['exam_paper_id' => $paper->id, 'student_id' => $child->id, 'score' => 50]);
        DB::table('exam_paper_submissions')->insert(['exam_paper_id' => $paper->id, 'status' => 'approved']);
        $payment = FeePayment::create(['school_id' => $this->school->id, 'term_id' => $this->term->id, 'student_id' => $child->id, 'amount' => ($index + 1) * 10000, 'method' => 'cash', 'transaction_id' => $name.' receipt', 'paid_at' => now()]);
        $payment->ledgerEntry()->update(['status' => 'posted']);
        return $child;
    });
});

it('keeps the results shortcut on the selected child and lists only their reports', function () {
    $child = $this->children[1];
    $this->actingAs($this->parent)->get(route('portal.home', ['student' => $child->id]))
        ->assertOk()->assertSee(route('my-results', ['student' => $child->id]), false);
    $this->get(route('my-results', ['student' => $child->id]))->assertOk()
        ->assertSee('Brian report')->assertDontSee('Alice report');
    $this->get(route('my-results'))->assertOk()->assertSee('Brian report')->assertDontSee('Alice report');
    $this->get(route('portal.home'))->assertOk()->assertSee('Brian receipt')->assertDontSee('Alice receipt');
    $exam = Exam::where('name', 'Brian report')->firstOrFail();
    $this->get(route('reports.student-term-report.show', [$child, $exam]))->assertOk()->assertSee('Brian');
});

it('does not offer a siblings class exam when the selected child has no marks', function () {
    $alice = $this->children[0];
    $brian = $this->children[1];
    $brian->update(['school_class_id' => $alice->school_class_id]);
    DB::table('exam_marks')->where('student_id', $brian->id)->delete();
    $exam = Exam::where('name', 'Alice report')->firstOrFail();
    $this->actingAs($this->parent)->get(route('portal.home', ['student' => $brian->id]))
        ->assertOk()->assertDontSee('Alice report')->assertSee('No published report cards yet.');
    $this->get(route('my-results', ['student' => $brian->id]))->assertOk()
        ->assertDontSee('Alice report')->assertSee('No published results are available for this learner yet.');
    $this->get(route('exams.report-card', [$exam, $brian]))->assertNotFound();
    $this->get(route('reports.student-term-report.show', [$brian, $exam]))->assertNotFound();
});

it('requires an approved non-null score but includes a genuine zero score', function () {
    $child = $this->children[0];
    $exam = Exam::where('name', 'Alice report')->firstOrFail();
    $paperId = $exam->papers()->value('id');
    $this->actingAs($this->parent);
    DB::table('exam_marks')->where('student_id', $child->id)->update(['score' => null]);
    $this->get(route('portal.home', ['student' => $child->id]))->assertOk()->assertDontSee('Alice report');
    DB::table('exam_marks')->where('student_id', $child->id)->update(['score' => 0]);
    $this->get(route('portal.home', ['student' => $child->id]))->assertOk()->assertSee('Alice report');
    DB::table('exam_paper_submissions')->where('exam_paper_id', $paperId)->update(['status' => 'submitted']);
    $this->get(route('portal.home', ['student' => $child->id]))->assertOk()->assertDontSee('Alice report');
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
    $this->get(route('my-results', ['student' => $stranger->id]))->assertNotFound();
});
