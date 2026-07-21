<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Arrears extends Model
{
    protected $table = 'arrears';

    protected $fillable = ['school_id', 'student_id', 'from_term_id', 'amount', 'applied_term_id', 'applied'];

    protected $casts = [
        'amount' => 'decimal:2',
        'applied' => 'boolean',
    ];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function fromTerm(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'from_term_id');
    }

    public function appliedTerm(): BelongsTo
    {
        return $this->belongsTo(Term::class, 'applied_term_id');
    }
}
