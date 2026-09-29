<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentEnrolment extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'term_id', 'school_class_id', 'stream_id',
        'student_category_id', 'fee_structure_id', 'base_fee_amount', 'status',
        'promotion_outcome', 'enrolled_at', 'exited_at', 'notes',
    ];

    protected $casts = [
        'base_fee_amount' => 'decimal:2',
        'enrolled_at' => 'date',
        'exited_at' => 'date',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function category(): BelongsTo { return $this->belongsTo(StudentCategory::class, 'student_category_id'); }
    public function feeStructure(): BelongsTo { return $this->belongsTo(FeeStructure::class); }
}
