<?php

use App\Models\AccountingJournal;
use App\Models\Expense;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\School;
use Database\Seeders\PublicDemoSchoolsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds balanced posted fee and expense examples for every public school type', function () {
    $this->seed(PublicDemoSchoolsSeeder::class);

    foreach (['kindergarten', 'primary', 'secondary', 'tertiary'] as $schoolType) {
        $school = School::where('is_demo', true)->where('school_type', $schoolType)->orderBy('id')->firstOrFail();

        expect(FeePayment::where('school_id', $school->id)->where('transaction_id', 'like', 'DEMO-FEE-%')->count())->toBe(1)
            ->and(Expense::where('school_id', $school->id)->where('reference_number', 'like', 'DEMO-EXP-%')->count())->toBe(2)
            ->and(FinanceLedgerEntry::where('school_id', $school->id)->where('status', 'posted')->whereIn('source_type', [FeePayment::class, Expense::class])->count())->toBeGreaterThanOrEqual(3);

        AccountingJournal::where('school_id', $school->id)->where('idempotency_key', 'like', 'demo-accounting:%')->get()->each(function ($journal) {
            expect($journal->status)->toBe('posted')
                ->and((float) $journal->lines()->sum('debit'))->toBe((float) $journal->lines()->sum('credit'));
        });
    }
});
