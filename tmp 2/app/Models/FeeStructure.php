<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeStructure extends Model
{
    protected $fillable = ['school_id', 'school_class_id', 'student_category_id', 'term_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function studentCategory(): BelongsTo
    {
        return $this->belongsTo(StudentCategory::class);
    }

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    /**
     * The fee amount for a given class + category, in a given term (or the
     * school's current term if none is passed). This is what student
     * registration will call to auto-map fees.
     */
    public static function amountFor(SchoolClass $class, StudentCategory $category, ?Term $term = null): ?float
    {
        $term ??= $class->school->currentTerm();

        if (! $term) {
            return null;
        }

        return static::where('school_class_id', $class->id)
            ->where('student_category_id', $category->id)
            ->where('term_id', $term->id)
            ->value('amount');
    }
}
