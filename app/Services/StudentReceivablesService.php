<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountMapping;
use App\Models\LedgerAccount;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\StudentEnrolment;
use App\Models\StudentFeeAdjustment;
use App\Models\StudentFeeAssessment;
use App\Models\Term;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentReceivablesService
{
    public function __construct(private readonly AccountingSetupService $setup, private readonly DoubleEntryService $journals) {}

    public function generateAssessments(School $school, Term $term, int $userId): array
    {
        abort_unless((int) $term->school_id === (int) $school->id, 404);
        $this->setup->activate($school, $userId);
        $receivable = $this->mapping($school->id, 'student_receivable');
        $income = $this->mapping($school->id, 'default_fee_income');
        $created = 0;
        $existing = 0;
        StudentEnrolment::where('school_id', $school->id)->where('term_id', $term->id)->where('status', 'active')->where('base_fee_amount', '>', 0)
            ->orderBy('id')->chunkById(100, function ($enrolments) use ($school, $term, $userId, $receivable, $income, &$created, &$existing) {
                foreach ($enrolments as $enrolment) {
                    DB::transaction(function () use ($enrolment, $school, $term, $userId, $receivable, $income, &$created, &$existing) {
                        $key = 'enrolment:'.$enrolment->id.':tuition';
                        $assessment = StudentFeeAssessment::where('school_id', $school->id)->where('idempotency_key', $key)->lockForUpdate()->first();
                        if ($assessment) {
                            $existing++;

                            return;
                        }
                        $assessment = StudentFeeAssessment::create(['school_id' => $school->id, 'student_id' => $enrolment->student_id, 'term_id' => $term->id, 'fee_structure_id' => $enrolment->fee_structure_id, 'fee_item_code' => 'tuition', 'description' => 'Tuition fee assessment - '.$term->name, 'amount' => $enrolment->base_fee_amount, 'status' => 'draft', 'idempotency_key' => $key, 'created_by' => $userId]);
                        $journalDate = (int) $term->year === (int) now()->year ? now()->toDateString() : $term->year.'-01-01';
                        $this->setup->ensurePeriods($school, (int) $term->year);
                        $journal = $this->journals->create(['school_id' => $school->id, 'term_id' => $term->id, 'journal_date' => $journalDate, 'reference' => 'ASSESS-'.$assessment->id, 'description' => $assessment->description, 'journal_type' => 'fee_assessment', 'currency' => $this->currency($school->id), 'source_type' => StudentFeeAssessment::class, 'source_id' => $assessment->id, 'idempotency_key' => 'fee_assessment:'.$assessment->id], [
                            ['ledger_account_id' => $receivable->id, 'term_id' => $term->id, 'student_id' => $enrolment->student_id, 'description' => $assessment->description, 'debit' => $assessment->amount, 'credit' => 0],
                            ['ledger_account_id' => $income->id, 'term_id' => $term->id, 'student_id' => $enrolment->student_id, 'description' => $assessment->description, 'debit' => 0, 'credit' => $assessment->amount],
                        ], $userId);
                        $this->journals->submit($journal, $userId);
                        $assessment->update(['journal_id' => $journal->id, 'status' => 'submitted']);
                        $created++;
                    });
                }
            });

        return compact('created', 'existing');
    }

    public function postAdjustment(StudentFeeAdjustment $adjustment, int $reviewerId): AccountingJournal
    {
        $debitType = in_array($adjustment->type, ['scholarship', 'staff_child', 'waiver'], true) ? 'scholarship' : 'fee_discount';
        $debit = $this->mapping($adjustment->school_id, $debitType);
        $receivable = $this->mapping($adjustment->school_id, 'student_receivable');

        return $this->journals->createAndPost(['school_id' => $adjustment->school_id, 'term_id' => $adjustment->term_id, 'journal_date' => $adjustment->reviewed_at?->toDateString() ?? now()->toDateString(), 'reference' => 'ADJ-'.$adjustment->id, 'description' => ucfirst(str_replace('_', ' ', $adjustment->type)).': '.$adjustment->reason, 'journal_type' => 'fee_adjustment', 'currency' => $this->currency($adjustment->school_id), 'source_type' => StudentFeeAdjustment::class, 'source_id' => $adjustment->id, 'idempotency_key' => 'fee_adjustment:'.$adjustment->id], [
            ['ledger_account_id' => $debit->id, 'term_id' => $adjustment->term_id, 'student_id' => $adjustment->student_id, 'description' => $adjustment->reason, 'debit' => $adjustment->amount, 'credit' => 0],
            ['ledger_account_id' => $receivable->id, 'term_id' => $adjustment->term_id, 'student_id' => $adjustment->student_id, 'description' => 'Reduce student receivable', 'debit' => 0, 'credit' => $adjustment->amount],
        ], $adjustment->requested_by, $reviewerId, true);
    }

    private function mapping(int $schoolId, string $type): LedgerAccount
    {
        $account = AccountMapping::with('account')->where('school_id', $schoolId)->where('mapping_type', $type)->whereNull('source_type')->whereNull('source_id')->first()?->account;
        if (! $account || ! $account->is_active || ! $account->accepts_postings || $account->currency !== $this->currency($schoolId)) {
            throw ValidationException::withMessages(['account_mapping' => "Configure the {$type} posting rule before continuing."]);
        }

        return $account;
    }

    private function currency(int $schoolId): string
    {
        return strtoupper((string) SchoolSetting::getValue($schoolId, 'accounting_currency', SchoolSetting::getValue($schoolId, 'currency', 'UGX')));
    }
}
