<?php

use App\Models\CashPoolEntry;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\School;
use Database\Seeders\AnnualDashboardDemoSeeder;
use Database\Seeders\TeacherSubjectVisibilitySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

it('seeds every elapsed month once with dashboard finance and academic data', function () {
    $this->seed(TeacherSubjectVisibilitySeeder::class);
    $this->seed(AnnualDashboardDemoSeeder::class);
    $this->seed(AnnualDashboardDemoSeeder::class);

    $school = School::where('school_number', 'EDL-TEACH')->firstOrFail();
    $months = now()->month;

    expect(FeePayment::where('school_id', $school->id)->where('transaction_id', 'like', 'ANNUAL-DEMO-PAY-%')->count())->toBe($months * 2)
        ->and(Expense::posted()->where('school_id', $school->id)->where('reference_number', 'like', 'ANNUAL-DEMO-EXP-%')->count())->toBe($months)
        ->and(CashPoolEntry::where('school_id', $school->id)->where('description', 'like', 'Synthetic annual dashboard%')->count())->toBe($months * 3)
        ->and(DB::table('exam_marks')->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
            ->join('exams', 'exams.id', '=', 'exam_papers.exam_id')->where('exams.school_id', $school->id)->count())->toBeGreaterThan(0);
});
