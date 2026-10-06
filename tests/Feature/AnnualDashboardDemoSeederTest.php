<?php

use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Student;
use App\Models\Term;
use App\Models\User;
use Database\Seeders\AnnualDashboardDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds every elapsed month once with dashboard finance and academic data', function () {
    $school = School::create(['name' => 'Annual Dashboard Demo', 'slug' => 'annual-dashboard-demo', 'school_number' => 'EDL-TEACH', 'is_demo' => true]);
    $term = Term::create(['school_id' => $school->id, 'name' => 'Current Term', 'year' => now()->year, 'is_current' => true, 'status' => 'open', 'locked' => false]);
    $class = SchoolClass::create(['school_id' => $school->id, 'name' => 'Primary Five', 'education_stage' => 'upper_primary', 'sort_order' => 5]);
    User::factory()->create(['school_id' => $school->id, 'role' => 'admin']);
    foreach (range(1, 2) as $number) {
        Student::create(['school_id' => $school->id, 'school_class_id' => $class->id, 'term_id' => $term->id,
            'name' => "Annual Learner {$number}", 'admission_no' => "AN-{$number}", 'status' => 'active']);
    }

    $this->seed(AnnualDashboardDemoSeeder::class);
    $this->seed(AnnualDashboardDemoSeeder::class);

    $months = now()->month;

    expect(FeePayment::where('school_id', $school->id)->where('transaction_id', 'like', 'ANNUAL-DEMO-PAY-%')->count())->toBe($months * 2)
        ->and(Expense::posted()->where('school_id', $school->id)->where('reference_number', 'like', 'ANNUAL-DEMO-EXP-%')->count())->toBe($months)
        ->and(CashPoolEntry::where('school_id', $school->id)->where('description', 'like', 'Synthetic annual dashboard%')->count())->toBe($months * 3)
        ->and(DB::table('exam_marks')->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')->where('exams.school_id', $school->id)->count())->toBeGreaterThan(0);
    expect(DB::table('exams')->where('school_id', $school->id)->distinct()->count('name'))->toBeGreaterThanOrEqual(4)
        ->and(DB::table('attendance_records')->where('school_id', $school->id)->where('session_key', 'annual-dashboard')->count())->toBe(10)
        ->and(DB::table('school_events')->where('school_id', $school->id)->where('title', 'like', 'Demo %')->count())->toBe(3)
        ->and(DB::table('students')->where('school_id', $school->id)->whereNotNull('gender')->count())->toBe(2);
});
