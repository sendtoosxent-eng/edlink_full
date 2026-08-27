<?php

namespace App\Livewire;

use App\Models\AccountingJournal;
use App\Models\AuditLog;
use App\Models\FeePayment;
use App\Models\FinanceLedgerEntry;
use App\Models\FinancialAccount;
use App\Models\SchoolSetting;
use App\Models\Student;
use App\Models\StudentFeeAdjustment;
use App\Services\DoubleEntryService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class FeePayments extends Component
{
    use WithPagination;

    public string $search = '';

    public ?int $payingStudentId = null;

    public string $amount = '';

    public string $method = 'cash';

    public string $financialAccountId = '';

    public string $notes = '';

    public string $transaction_id = '';

    public string $bank_slip_number = '';

    public ?int $deletingPaymentId = null;

    public string $adjustmentType = 'negotiated';

    public string $adjustmentCalculation = 'fixed';

    public string $adjustmentValue = '';

    public string $adjustmentReason = '';

    public string $reviewNotes = '';

    public bool $approvalScreen = false;

    public function mount(): void
    {
        $this->approvalScreen = request()->query('screen') === 'adjustments';
        $studentId = request()->integer('student');
        if ($studentId && Student::where('school_id', Auth::user()->school_id)->where('status', 'active')->whereKey($studentId)->exists()) {
            $this->openPaymentForm($studentId);
        }
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function openPaymentForm(int $studentId): void
    {
        $this->resetValidation();
        $this->reset(['amount', 'method', 'notes', 'transaction_id', 'bank_slip_number', 'adjustmentValue', 'adjustmentReason', 'reviewNotes']);
        $this->method = 'cash';
        $this->syncFinancialAccount();
        $this->payingStudentId = $studentId;
    }

    public function updatedMethod(): void
    {
        $this->syncFinancialAccount();
    }

    public function cancelPayment(): void
    {
        $this->payingStudentId = null;
    }

    public function confirmDelete(int $paymentId): void
    {
        $this->deletingPaymentId = $paymentId;
    }

    public function cancelDelete(): void
    {
        $this->deletingPaymentId = null;
    }

    public function deletePayment(int $paymentId): void
    {
        abort_unless(Auth::user()->hasPermission('finance.payments'), 403);

        $school = Auth::user()->school;
        $term = $school->currentTerm();
        $payment = FeePayment::where('school_id', $school->id)->findOrFail($paymentId);

        if (! $term || ! $term->isOpen() || $payment->term_id !== $term->id) {
            session()->flash('error', 'Only payments in the current open term can be deleted.');
            $this->deletingPaymentId = null;

            return;
        }

        $ledger = FinanceLedgerEntry::where(['source_type' => FeePayment::class, 'source_id' => $payment->id])->first();
        if ($ledger && $ledger->status !== 'pending') {
            session()->flash('error', 'Posted payments cannot be deleted. Reverse the transaction from Ledger & Reconciliation.');
            $this->deletingPaymentId = null;

            return;
        }
        DB::transaction(fn () => $payment->delete());
        $this->deletingPaymentId = null;
        session()->flash('status', 'Pending payment deleted. It had not affected the cash pool.');
    }

    public function recordPayment(): void
    {
        abort_unless(Auth::user()->hasPermission('finance.payments'), 403);

        $school = Auth::user()->school;
        $term = $school->currentTerm();

        if (! $term) {
            session()->flash('error', 'No open term — can\'t record a payment.');

            return;
        }

        if (! $term->isEditable()) {
            session()->flash('error', 'This term is locked and can no longer accept payments.');

            return;
        }
        if ($this->financialAccountId === '') {
            $this->syncFinancialAccount();
        }

        $this->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'in:cash,mobile_money,bank,other'],
            'financialAccountId' => ['required', Rule::exists('financial_accounts', 'id')->where('school_id', $school->id)->where('is_active', true)],
            'transaction_id' => [$this->method === 'mobile_money' ? 'required' : 'nullable', 'string', 'max:100'],
            'bank_slip_number' => [$this->method === 'bank' ? 'required' : 'nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $student = Student::where('school_id', $school->id)->findOrFail($this->payingStudentId);

        $payment = DB::transaction(function () use ($school, $student, $term) {
            $payment = FeePayment::create([
                'school_id' => $school->id, 'financial_account_id' => $this->financialAccountId, 'student_id' => $student->id,
                'term_id' => $term->id, 'amount' => $this->amount,
                'method' => $this->method, 'transaction_id' => $this->transaction_id ?: null, 'bank_slip_number' => $this->bank_slip_number ?: null, 'notes' => $this->notes ?: null,
                'recorded_by' => Auth::id(), 'paid_at' => now(),
            ]);

            return $payment;
        });

        $this->payingStudentId = null;
        $currency = (string) SchoolSetting::getValue($school->id, 'currency', 'UGX');
        session()->flash('status', 'Payment of '.$currency.' '.number_format((float) $this->amount).' recorded for '.$student->name.' and sent for approval. It will affect the balance and cash pool after approval.');
    }

    public function requestAdjustment(): void
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('finance.adjustments'), 403);
        $term = $user->school->currentTerm();
        if (! $term?->isEditable()) {
            session()->flash('error', 'Fee adjustments can only be requested in the current editable term.');

            return;
        }

        $this->validate([
            'adjustmentType' => ['required', 'in:negotiated,waiver,scholarship,staff_child,correction'],
            'adjustmentCalculation' => ['required', 'in:fixed,percentage,final_fee'],
            'adjustmentValue' => ['required', 'numeric', 'min:0.01'],
            'adjustmentReason' => ['required', 'string', 'min:10', 'max:2000'],
        ]);

        $adjustment = DB::transaction(function () use ($user, $term): ?StudentFeeAdjustment {
            // Requests and reviews lock the same student row. This makes
            // the initial-fee cap reliable when two finance users act at once.
            $student = Student::where('school_id', $user->school_id)
                ->where('status', 'active')->lockForUpdate()->findOrFail($this->payingStudentId);
            $baseFee = (float) ($student->mappedFeeAmount($term) ?? 0);
            if ($baseFee <= 0) {
                $this->addError('adjustmentValue', 'This learner has no mapped fee for the current term.');

                return null;
            }

            $value = (float) $this->adjustmentValue;
            $amount = match ($this->adjustmentCalculation) {
                'percentage' => round($baseFee * $value / 100, 2),
                'final_fee' => round($baseFee - $value, 2),
                default => round($value, 2),
            };
            if (($this->adjustmentCalculation === 'percentage' && $value > 100)
                || ($this->adjustmentCalculation === 'final_fee' && $value >= $baseFee)
                || $amount <= 0) {
                $this->addError('adjustmentValue', 'Enter a reduction that leaves a valid adjusted fee. Use a waiver for a full fee reduction.');

                return null;
            }

            $existing = $student->feeAdjustments()->where('term_id', $term->id)->whereIn('status', ['pending', 'approved']);
            if ($this->adjustmentCalculation === 'final_fee' && (clone $existing)->exists()) {
                $this->addError('adjustmentValue', 'A final agreed fee cannot be combined with another active adjustment.');

                return null;
            }
            if ((clone $existing)->where('calculation_type', 'final_fee')->exists()) {
                $this->addError('adjustmentValue', 'This learner already has a final agreed fee awaiting or holding approval.');

                return null;
            }
            if ((float) (clone $existing)->sum('amount') + $amount > $baseFee) {
                $this->addError('adjustmentValue', 'Active adjustments cannot exceed this learner\'s initial term fee.');

                return null;
            }

            $adjustment = StudentFeeAdjustment::create([
                'school_id' => $user->school_id,
                'student_id' => $student->id,
                'term_id' => $term->id,
                'requested_by' => $user->id,
                'type' => $this->adjustmentType,
                'calculation_type' => $this->adjustmentCalculation,
                'value' => $value,
                'amount' => $amount,
                'reason' => $this->adjustmentReason,
                'status' => 'pending',
            ]);
            AuditLog::record($user->school_id, 'finance.fee_adjustment.requested', $adjustment, [
                'student_id' => $student->id, 'term_id' => $term->id, 'amount' => $amount,
            ]);

            return $adjustment;
        });

        if (! $adjustment) {
            return;
        }

        $this->reset(['adjustmentValue', 'adjustmentReason']);
        $studentName = Student::where('school_id', $user->school_id)->whereKey($adjustment->student_id)->value('name');
        session()->flash(
            'status',
            'Adjustment request #'.$adjustment->id.' was submitted successfully for '.($studentName ?: 'the learner')
            .' ('.(string) SchoolSetting::getValue($user->school_id, 'currency', 'UGX').' '.number_format((float) $adjustment->amount).'). Status: PENDING APPROVAL. '
            .'The learner balance has not changed. A finance approver can review it on the Fee Adjustments screen.'
        );
    }

    public function reviewAdjustment(int $adjustmentId, string $decision): void
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('finance.adjustments'), 403);
        abort_unless(in_array($decision, ['approved', 'rejected'], true), 422);
        $term = $user->school->currentTerm();
        if (! $term?->isEditable()) {
            session()->flash('error', 'Adjustments cannot be reviewed after the term is locked.');

            return;
        }

        $reviewed = DB::transaction(function () use ($user, $term, $adjustmentId, $decision): bool {
            $pending = StudentFeeAdjustment::where('school_id', $user->school_id)
                ->where('term_id', $term->id)->findOrFail($adjustmentId);
            $student = Student::where('school_id', $user->school_id)
                ->lockForUpdate()->findOrFail($pending->student_id);
            $adjustment = StudentFeeAdjustment::where('school_id', $user->school_id)
                ->where('term_id', $term->id)->lockForUpdate()->findOrFail($adjustmentId);
            if ($adjustment->status !== 'pending') {
                return false;
            }

            if ($decision === 'approved') {
                $baseFee = (float) ($student->mappedFeeAmount($term) ?? 0);
                $approved = (float) $student->feeAdjustments()
                    ->where('term_id', $term->id)->where('status', 'approved')->sum('amount');
                $amount = (float) $adjustment->amount;
                if ($baseFee <= 0 || $amount <= 0 || $approved + $amount > $baseFee) {
                    return false;
                }
            }

            $adjustment->update([
                'status' => $decision,
                'reviewed_by' => $user->id,
                'review_notes' => $this->reviewNotes ?: null,
                'reviewed_at' => now(),
            ]);
            AuditLog::record($user->school_id, 'finance.fee_adjustment.'.$decision, $adjustment, [
                'student_id' => $adjustment->student_id,
                'term_id' => $adjustment->term_id,
                'amount' => (float) $adjustment->amount,
            ]);

            return true;
        });

        if (! $reviewed) {
            session()->flash('error', 'This adjustment could not be reviewed because it is no longer pending or would exceed the learner\'s initial term fee.');

            return;
        }

        $this->reviewNotes = '';
        session()->flash('status', 'Fee adjustment '.$decision.'.');
    }

    public function cancelAdjustment(int $adjustmentId): void
    {
        $user = Auth::user();
        abort_unless($user->hasPermission('finance.adjustments'), 403);
        $term = $user->school->currentTerm();
        if (! $term?->isEditable()) {
            session()->flash('error', 'Approved adjustments cannot be cancelled after the term is locked.');

            return;
        }

        DB::transaction(function () use ($user, $term, $adjustmentId): void {
            $approved = StudentFeeAdjustment::where('school_id', $user->school_id)
                ->where('term_id', $term->id)->where('status', 'approved')->findOrFail($adjustmentId);
            Student::where('school_id', $user->school_id)->lockForUpdate()->findOrFail($approved->student_id);
            $adjustment = StudentFeeAdjustment::where('school_id', $user->school_id)
                ->where('term_id', $term->id)->where('status', 'approved')->lockForUpdate()->findOrFail($adjustmentId);
            $journal = AccountingJournal::where('school_id', $user->school_id)->where('source_type', StudentFeeAdjustment::class)->where('source_id', $adjustment->id)->where('status', 'posted')->first();
            if ($journal) {
                app(DoubleEntryService::class)->reverse($journal, $this->reviewNotes ?: 'Approved fee adjustment cancelled', $user->id);
            }
            $adjustment->update([
                'status' => 'cancelled',
                'reviewed_by' => $user->id,
                'review_notes' => $this->reviewNotes ?: 'Cancelled by finance staff.',
                'reviewed_at' => now(),
            ]);
            AuditLog::record($user->school_id, 'finance.fee_adjustment.cancelled', $adjustment, [
                'student_id' => $adjustment->student_id,
                'term_id' => $adjustment->term_id,
                'amount' => (float) $adjustment->amount,
            ]);
        });
        $this->reviewNotes = '';
        session()->flash('status', 'Approved fee adjustment cancelled and the learner balance restored.');
    }

    public function render()
    {
        $school = Auth::user()->school;
        $term = $school->currentTerm();

        if ($this->approvalScreen) {
            abort_unless(Auth::user()->hasPermission('finance.adjustments'), 403);
            $adjustments = StudentFeeAdjustment::with(['student.schoolClass', 'requester', 'reviewer'])
                ->where('school_id', $school->id)
                ->when($term, fn ($query) => $query->where('term_id', $term->id));

            return view('livewire.fee-adjustments', [
                'term' => $term,
                'pendingAdjustments' => (clone $adjustments)->where('status', 'pending')->latest()->get(),
                'reviewedAdjustments' => (clone $adjustments)->whereIn('status', ['approved', 'rejected', 'cancelled'])->latest('reviewed_at')->limit(30)->get(),
                'pageTitle' => 'Fee Adjustments',
            ]);
        }

        $students = Student::with(['schoolClass', 'category', 'feeAdjustments'])
            ->where('school_id', $school->id)
            ->where('status', 'active')
            ->when($this->search, fn ($q) => $q->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('admission_no', 'like', '%'.$this->search.'%');
            }))
            ->orderBy('name')
            ->paginate(15);

        $payments = FeePayment::with(['student.schoolClass', 'term', 'recordedBy'])
            ->where('school_id', $school->id)
            ->latest('paid_at')
            ->paginate(10, ['*'], 'paymentsPage');

        $selectedStudent = $this->payingStudentId
            ? Student::with(['schoolClass', 'category', 'feeAdjustments.requester', 'feeAdjustments.reviewer'])
                ->where('school_id', $school->id)->find($this->payingStudentId)
            : null;

        return view('livewire.fee-payments', [
            'students' => $students,
            'payments' => $payments,
            'term' => $term,
            'selectedStudent' => $selectedStudent,
            'pendingAdjustments' => $term ? StudentFeeAdjustment::with(['student', 'requester'])
                ->where('school_id', $school->id)->where('term_id', $term->id)->where('status', 'pending')->latest()->get() : collect(),
            'canRequestAdjustments' => Auth::user()->hasPermission('finance.adjustments'),
            'canApproveAdjustments' => Auth::user()->hasPermission('finance.adjustments'),
            'canRecordPayments' => Auth::user()->hasPermission('finance.payments'),
            'pageTitle' => 'Fee Payments',
            'paymentAccounts' => FinancialAccount::where('school_id', $school->id)->where('is_active', true)->whereNotNull('ledger_account_id')->orderBy('name')->get(),
        ]);
    }

    private function syncFinancialAccount(): void
    {
        $type = $this->method === 'mobile_money' ? 'mobile_money' : $this->method;
        $this->financialAccountId = (string) FinancialAccount::where('school_id', Auth::user()->school_id)->where('type', $type)->where('is_active', true)->whereNotNull('ledger_account_id')->value('id');
    }
}
