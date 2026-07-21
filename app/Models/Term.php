<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class Term extends Model
{
    protected $fillable = ['school_id', 'name', 'year', 'is_current', 'status', 'locked', 'closed_at'];

    protected $casts = [
        'is_current' => 'boolean',
        'locked' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function students(): HasMany
    {
        return $this->hasMany(Student::class);
    }

    public function enrolments(): HasMany
    {
        return $this->hasMany(StudentEnrolment::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public static function currentFor(School $school): ?self
    {
        return static::where('school_id', $school->id)->where('is_current', true)->first();
    }

    public function isOpen(): bool
    {
        return $this->status === 'open';
    }

    public function isLocked(): bool
    {
        return $this->locked;
    }

    /**
     * Records can be added/edited for this term only if it's open, or closed
     * WITHOUT arrears having been rolled. A term closed WITH arrears rolled
     * is permanently locked.
     */
    public function isEditable(): bool
    {
        return $this->isOpen();
    }

    /**
     * Open this term. Only one term can be open per school at a time, so any
     * currently-open term must be closed first.
     *
     * @throws \RuntimeException
     */
    public function openTerm(): void
    {
        $alreadyOpen = static::where('school_id', $this->school_id)
            ->where('is_current', true)
            ->where('id', '!=', $this->id)
            ->exists();

        if ($alreadyOpen) {
            throw new \RuntimeException('Another term is already open. Close it before opening this one.');
        }

        $this->update(['is_current' => true, 'status' => 'open', 'closed_at' => null]);

        // Any arrears waiting to be applied get attached to this newly opened term.
        Arrears::where('school_id', $this->school_id)
            ->where('applied', false)
            ->update(['applied_term_id' => $this->id, 'applied' => true]);

        // Placement is prepared while a term is pending. Activating it makes
        // those reviewed records operational and updates the learner's
        // convenience "current" fields used by older screens.
        $this->enrolments()->where('status', 'pending')->each(function (StudentEnrolment $enrolment) {
            $enrolment->update(['status' => 'active']);
            $enrolment->student->update([
                'school_class_id' => $enrolment->school_class_id,
                'stream_id' => $enrolment->stream_id,
                'student_category_id' => $enrolment->student_category_id,
                'term_id' => $this->id,
                'status' => 'active',
            ]);
        });
    }

    /**
     * Copy active enrolments to a future term for review. The source term is
     * never modified, and each copied fee is re-snapshotted from the target
     * term's fee structure.
     */
    public function prepareEnrolmentsFor(Term $targetTerm): int
    {
        if ($this->school_id !== $targetTerm->school_id) {
            throw new \RuntimeException('Terms must belong to the same school.');
        }

        if ($this->status !== 'closed') {
            throw new \RuntimeException('Close the source term before preparing next-term enrolments.');
        }

        if (! in_array($targetTerm->status, ['pending', 'open'], true)) {
            throw new \RuntimeException('Enrolments can only be prepared for a pending or open term.');
        }

        return DB::transaction(function () use ($targetTerm) {
            $created = 0;

            $this->enrolments()->where('status', 'active')->each(function (StudentEnrolment $enrolment) use ($targetTerm, &$created) {
                $exists = StudentEnrolment::where('student_id', $enrolment->student_id)
                    ->where('term_id', $targetTerm->id)
                    ->exists();

                if ($exists) {
                    return;
                }

                $feeStructure = FeeStructure::where('school_id', $this->school_id)
                    ->where('school_class_id', $enrolment->school_class_id)
                    ->where('student_category_id', $enrolment->student_category_id)
                    ->where('term_id', $targetTerm->id)
                    ->first();

                StudentEnrolment::create([
                    'school_id' => $this->school_id,
                    'student_id' => $enrolment->student_id,
                    'term_id' => $targetTerm->id,
                    'school_class_id' => $enrolment->school_class_id,
                    'stream_id' => $enrolment->stream_id,
                    'student_category_id' => $enrolment->student_category_id,
                    'fee_structure_id' => $feeStructure?->id,
                    'base_fee_amount' => $feeStructure?->amount ?? 0,
                    'status' => $targetTerm->isOpen() ? 'active' : 'pending',
                    'enrolled_at' => $targetTerm->created_at->toDateString(),
                ]);

                $created++;
            });

            return $created;
        });
    }

    /**
     * Close this term.
     *
     * @param bool $rollArrears If true, unpaid balances are calculated per
     *   active student and stored as Arrears to be applied to whichever term
     *   opens next — and this term becomes permanently locked (no further
     *   edits to fees or records). If false, the term simply closes and
     *   remains editable.
     */
    public function closeTerm(bool $rollArrears): void
    {
        DB::transaction(function () use ($rollArrears) {
            if (! $this->isOpen()) {
                throw new \RuntimeException('Only an open term can be closed.');
            }

            if ($rollArrears) {
                foreach ($this->enrolments()->with('student')->where('status', 'active')->whereHas('student', fn ($query) => $query->where('status', 'active'))->get() as $enrolment) {
                    $student = $enrolment->student;
                    $expected = (float) $enrolment->base_fee_amount;
                    $paid = $this->feePayments()->where('student_id', $student->id)->sum('amount');
                    $balance = $expected - $paid;

                    if ($balance > 0) {
                        Arrears::create([
                            'school_id' => $this->school_id,
                            'student_id' => $student->id,
                            'from_term_id' => $this->id,
                            'amount' => $balance,
                            'applied' => false,
                        ]);
                    }
                }
            }

            $this->update([
                'is_current' => false,
                'status' => 'closed',
                'locked' => $rollArrears,
                'closed_at' => now(),
            ]);
        });
    }
}
