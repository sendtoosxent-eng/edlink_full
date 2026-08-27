<?php

namespace App\Services;

use App\Models\AccountingJournal;
use App\Models\AccountingJournalLine;
use App\Models\AccountingPeriod;
use App\Models\AuditLog;
use App\Models\LedgerAccount;
use App\Models\StudentFeeAssessment;
use App\Support\AccountingMoney;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class DoubleEntryService
{
    public function create(array $header, array $lines, int $userId): AccountingJournal
    {
        return DB::transaction(function () use ($header, $lines, $userId) {
            $schoolId = (int) $header['school_id'];
            $period = AccountingPeriod::where('school_id', $schoolId)->whereDate('starts_on', '<=', $header['journal_date'])->whereDate('ends_on', '>=', $header['journal_date'])->firstOrFail();
            $journal = AccountingJournal::create($header + [
                'accounting_period_id' => $period->id, 'number' => $this->nextNumber($schoolId), 'status' => 'draft', 'created_by' => $userId,
            ]);
            $this->replaceDraftLines($journal, $lines);
            AuditLog::record($schoolId, 'accounting.journal.created', $journal, ['number' => $journal->number]);

            return $journal->load('lines');
        });
    }

    public function createAndPost(array $header, array $lines, int $preparedBy, int $approvedBy, bool $sourceAlreadyApproved = false): AccountingJournal
    {
        $journal = $this->create($header, $lines, $preparedBy);
        $this->submit($journal, $preparedBy);
        if ($sourceAlreadyApproved && $preparedBy === $approvedBy) {
            $journal->update(['status' => 'approved', 'submitted_by' => $preparedBy, 'submitted_at' => now(), 'approved_by' => $approvedBy, 'approved_at' => now()]);
            AuditLog::record($journal->school_id, 'accounting.journal.source_approval_inherited', $journal, ['source_type' => $journal->source_type, 'source_id' => $journal->source_id]);
        } else {
            $this->approve($journal->fresh(), $approvedBy);
        }

        return $this->post($journal->fresh(), $approvedBy);
    }

    public function replaceDraftLines(AccountingJournal $journal, array $lines): void
    {
        abort_unless($journal->status === 'draft', 422, 'Only draft journals may be edited.');
        $normalized = $this->validateLines($journal->school_id, $journal->currency, $lines);
        $journal->lines()->delete();
        foreach ($normalized as $line) {
            AccountingJournalLine::create($line + ['school_id' => $journal->school_id, 'accounting_journal_id' => $journal->id]);
        }
    }

    public function submit(AccountingJournal $journal, int $userId): void
    {
        DB::transaction(function () use ($journal, $userId) {
            $journal = AccountingJournal::lockForUpdate()->with('lines')->findOrFail($journal->id);
            if ($journal->status !== 'draft') {
                $this->fail('journal', 'Only draft journals can be submitted.');
            }$this->validateLines($journal->school_id, $journal->currency, $journal->lines->toArray());
            $journal->update(['status' => 'submitted', 'submitted_by' => $userId, 'submitted_at' => now()]);
            AuditLog::record($journal->school_id, 'accounting.journal.submitted', $journal);
        });
    }

    public function approve(AccountingJournal $journal, int $userId): void
    {
        DB::transaction(function () use ($journal, $userId) {
            $journal = AccountingJournal::lockForUpdate()->findOrFail($journal->id);
            if ($journal->status !== 'submitted') {
                $this->fail('journal', 'Only submitted journals can be approved.');
            }if ($journal->created_by === $userId || $journal->submitted_by === $userId) {
                $this->fail('journal', 'Maker-checker control requires a different approver.');
            }$journal->update(['status' => 'approved', 'approved_by' => $userId, 'approved_at' => now()]);
            AuditLog::record($journal->school_id, 'accounting.journal.approved', $journal);
        });
    }

    public function reject(AccountingJournal $journal, string $reason, int $userId): void
    {
        DB::transaction(function () use ($journal, $reason, $userId) {
            $journal = AccountingJournal::lockForUpdate()->findOrFail($journal->id);
            if (! in_array($journal->status, ['submitted', 'approved'], true)) {
                $this->fail('journal', 'Only submitted or approved journals can be rejected.');
            }if ($journal->created_by === $userId) {
                $this->fail('journal', 'The preparer cannot reject their own journal.');
            }$journal->update(['status' => 'rejected', 'rejection_reason' => $reason, 'rejected_by' => $userId, 'rejected_at' => now()]);
            AuditLog::record($journal->school_id, 'accounting.journal.rejected', $journal, ['reason' => $reason]);
        });
    }

    public function post(AccountingJournal $journal, int $userId): AccountingJournal
    {
        return DB::transaction(function () use ($journal, $userId) {
            $journal = AccountingJournal::lockForUpdate()->with(['lines.account', 'period'])->findOrFail($journal->id);
            if ($journal->status !== 'approved') {
                $this->fail('journal', 'Only approved journals can be posted.');
            }if (! $journal->period->acceptsPosting()) {
                $this->fail('journal', 'The accounting period is not open.');
            }$this->validateLines($journal->school_id, $journal->currency, $journal->lines->toArray());
            $journal->update(['status' => 'posted', 'posted_by' => $userId, 'posted_at' => now()]);
            if ($journal->source_type === StudentFeeAssessment::class && $journal->source_id) {
                StudentFeeAssessment::where('school_id', $journal->school_id)->whereKey($journal->source_id)->update(['status' => 'posted', 'posted_at' => now()]);
            }
            AuditLog::record($journal->school_id, 'accounting.journal.posted', $journal);

            return $journal;
        });
    }

    public function reverse(AccountingJournal $original, string $reason, int $userId): AccountingJournal
    {
        if (strlen(trim($reason)) < 8) {
            $this->fail('reason', 'A meaningful reversal reason is required.');
        }

        return DB::transaction(function () use ($original, $reason, $userId) {
            $original = AccountingJournal::lockForUpdate()->with('lines')->findOrFail($original->id);
            if ($original->status !== 'posted') {
                $this->fail('journal', 'Only posted journals may be reversed.');
            }if (AccountingJournal::where('reversal_of_id', $original->id)->exists()) {
                $this->fail('journal', 'This journal has already been reversed.');
            }$lines = $original->lines->map(fn ($line) => ['ledger_account_id' => $line->ledger_account_id, 'term_id' => $line->term_id, 'cost_centre_id' => $line->cost_centre_id, 'fund_id' => $line->fund_id, 'student_id' => $line->student_id, 'employee_id' => $line->employee_id, 'supplier_id' => $line->supplier_id, 'description' => 'Reversal: '.$line->description, 'debit' => $line->credit, 'credit' => $line->debit])->all();
            $reversal = $this->create(['school_id' => $original->school_id, 'term_id' => $original->term_id, 'journal_date' => now()->toDateString(), 'reference' => 'REV-'.$original->number, 'description' => 'Reversal of '.$original->number.': '.$reason, 'journal_type' => 'reversal', 'currency' => $original->currency, 'reversal_of_id' => $original->id, 'reversal_reason' => $reason, 'idempotency_key' => 'reversal:'.$original->id], $lines, $userId);
            $reversal->update(['status' => 'approved', 'submitted_by' => $userId, 'submitted_at' => now(), 'approved_by' => $userId, 'approved_at' => now()]);
            $this->post($reversal->fresh(), $userId);
            $original->update(['status' => 'reversed', 'reversal_reason' => $reason]);
            AuditLog::record($original->school_id, 'accounting.journal.reversed', $reversal, ['original_journal_id' => $original->id, 'reason' => $reason]);

            return $reversal->fresh();
        });
    }

    public function validateLines(int $schoolId, string $currency, array $lines): array
    {
        if (count($lines) < 2) {
            $this->fail('lines', 'A journal requires at least two lines.');
        }
        $debits = 0;
        $credits = 0;
        $normalized = [];
        foreach ($lines as $index => $line) {
            $debit = AccountingMoney::minor((string) ($line['debit'] ?? '0'));
            $credit = AccountingMoney::minor((string) ($line['credit'] ?? '0'));
            if (($debit > 0 && $credit > 0) || ($debit === 0 && $credit === 0) || $debit < 0 || $credit < 0) {
                $this->fail("lines.{$index}", 'Each line must contain one positive debit or one positive credit.');
            }$account = LedgerAccount::where('school_id', $schoolId)->whereKey($line['ledger_account_id'])->first();
            if (! $account || ! $account->is_active || ! $account->accepts_postings) {
                $this->fail("lines.{$index}.ledger_account_id", 'Select an active posting account from this school.');
            }if ($account->currency !== $currency) {
                $this->fail("lines.{$index}.ledger_account_id", 'The account currency does not match the journal currency.');
            }
            foreach (['term_id' => 'terms', 'cost_centre_id' => 'cost_centres', 'fund_id' => 'accounting_funds', 'student_id' => 'students', 'employee_id' => 'users', 'supplier_id' => 'accounting_suppliers'] as $field => $table) {
                if (($line[$field] ?? null) && ! DB::table($table)->where('school_id', $schoolId)->where('id', $line[$field])->exists()) {
                    $this->fail("lines.{$index}.{$field}", 'The selected accounting dimension does not belong to this school.');
                }
            }
            $debits += $debit;
            $credits += $credit;
            $normalized[] = ['ledger_account_id' => $account->id, 'term_id' => $line['term_id'] ?? null, 'cost_centre_id' => $line['cost_centre_id'] ?? null, 'fund_id' => $line['fund_id'] ?? null, 'student_id' => $line['student_id'] ?? null, 'employee_id' => $line['employee_id'] ?? null, 'supplier_id' => $line['supplier_id'] ?? null, 'description' => $line['description'] ?? null, 'debit' => AccountingMoney::decimal($debit), 'credit' => AccountingMoney::decimal($credit)];
        }
        if ($debits !== $credits) {
            $this->fail('lines', 'Total debits must equal total credits exactly.');
        }

        return $normalized;
    }

    private function nextNumber(int $schoolId): string
    {
        return 'JRN-'.now()->format('Ym').'-'.str_pad((string) (AccountingJournal::where('school_id', $schoolId)->lockForUpdate()->count() + 1), 6, '0', STR_PAD_LEFT).'-'.Str::upper(Str::random(3));
    }

    private function fail(string $key, string $message): never
    {
        throw ValidationException::withMessages([$key => $message]);
    }
}
