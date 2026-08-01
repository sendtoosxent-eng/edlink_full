<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

class Student extends Model
{
    use HasFactory;

    protected $fillable = [
        'school_id', 'school_class_id', 'stream_id', 'student_category_id', 'term_id', 'status',
        'name', 'admission_no',
        'date_of_birth', 'gender', 'admission_date', 'photo_path',
        'nationality', 'religion', 'blood_group', 'home_address', 'medical_notes',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
        'admission_date' => 'date',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class, 'student_category_id');
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function guardians(): HasMany
    {
        return $this->hasMany(StudentGuardian::class);
    }

    /** A permanent record of each term's placement and mapped fee. */
    public function enrolments(): HasMany
    {
        return $this->hasMany(StudentEnrolment::class);
    }

    public function subjectSelections(): HasMany
    {
        return $this->hasMany(StudentSubjectSelection::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }

    public function arrears(): HasMany
    {
        return $this->hasMany(Arrears::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function photoUrl(): ?string
    {
        return $this->photo_path ? Storage::disk('public')->url($this->photo_path) : null;
    }

    /**
     * The base fee for this student's class + category, in a given term
     * (defaults to the school's current term).
     */
    public function mappedFeeAmount(?Term $term = null): ?float
    {
        $term ??= $this->school->currentTerm();

        if (! $term) {
            return null;
        }

        // Enrolments are the accounting record. Their fee is a snapshot taken
        // when the learner joined the term, so later edits to fee structures
        // cannot alter an already-opened or closed term.
        $enrolment = $this->enrolments()->where('term_id', $term->id)->first();

        if ($enrolment) {
            return (float) $enrolment->base_fee_amount;
        }

        if (! $this->schoolClass || ! $this->category) {
            return null;
        }

        return FeeStructure::amountFor($this->schoolClass, $this->category, $term);
    }

    /**
     * Any arrears applied to the given term (defaults to current).
     */
    public function arrearsDueIn(?Term $term = null): float
    {
        if (! $this->isActive()) {
            return 0;
        }

        $term ??= $this->school->currentTerm();

        if (! $term) {
            return 0;
        }

        return (float) $this->arrears()->where('applied_term_id', $term->id)->sum('amount');
    }

    /**
     * Total amount owed this term: base fee + any arrears applied to it.
     */
    public function totalDue(?Term $term = null): float
    {
        if (! $this->isActive()) {
            return 0;
        }

        $term ??= $this->school->currentTerm();

        return ($this->mappedFeeAmount($term) ?? 0) + $this->arrearsDueIn($term);
    }

    public function totalPaid(?Term $term = null): float
    {
        $term ??= $this->school->currentTerm();

        if (! $term) {
            return 0;
        }

        return (float) $this->feePayments()->where('term_id', $term->id)->whereExists(function ($query) {
            $query->selectRaw('1')->from('finance_ledger_entries')->whereColumn('finance_ledger_entries.source_id', 'fee_payments.id')->where('finance_ledger_entries.source_type', FeePayment::class)->where('finance_ledger_entries.status', 'posted');
        })->sum('amount');
    }

    public function balance(?Term $term = null): float
    {
        if (! $this->isActive()) {
            return 0;
        }

        return $this->totalDue($term) - $this->totalPaid($term);
    }
}
