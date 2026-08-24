<?php

namespace App\Livewire;

use App\Models\AuditLog;
use App\Models\Student;
use App\Models\StudentFeeAdjustment;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class FeeAdjustments extends Component
{
    public string $reviewNotes = '';

    public function mount(): void
    {
        abort_unless(Auth::user()->hasPermission('finance.adjustments'), 403);
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

            if ($adjustment->status !== 'pending') return false;

            if ($decision === 'approved') {
                $baseFee = (float) ($student->mappedFeeAmount($term) ?? 0);
                $approved = (float) $student->feeAdjustments()->where('term_id', $term->id)
                    ->where('status', 'approved')->sum('amount');
                if ($baseFee <= 0 || (float) $adjustment->amount <= 0 || $approved + (float) $adjustment->amount > $baseFee) return false;
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
            session()->flash('error', 'This request is no longer pending or would exceed the learner’s original term fee.');
            return;
        }

        $this->reviewNotes = '';
        session()->flash('status', 'Fee adjustment '.$decision.' successfully.');
    }

    public function render()
    {
        $user = Auth::user();
        $term = $user->school->currentTerm();
        $query = StudentFeeAdjustment::with(['student.schoolClass', 'requester', 'reviewer'])
            ->where('school_id', $user->school_id)
            ->when($term, fn ($builder) => $builder->where('term_id', $term->id));

        return view('livewire.fee-adjustments', [
            'term' => $term,
            'pendingAdjustments' => (clone $query)->where('status', 'pending')->latest()->get(),
            'reviewedAdjustments' => (clone $query)->whereIn('status', ['approved', 'rejected', 'cancelled'])->latest('reviewed_at')->limit(30)->get(),
            'pageTitle' => 'Fee Adjustments',
        ]);
    }
}
