<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentFeeAdjustment extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'term_id', 'requested_by', 'reviewed_by',
        'type', 'calculation_type', 'value', 'amount', 'reason', 'status',
        'review_notes', 'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
            'reviewed_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function requester(): BelongsTo { return $this->belongsTo(User::class, 'requested_by'); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
