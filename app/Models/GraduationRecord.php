<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GraduationRecord extends Model
{
    protected $fillable = [
        'school_id', 'student_id', 'term_id', 'school_class_id', 'graduation_year',
        'graduated_at', 'final_average', 'outstanding_balance', 'certificate_number',
        'portal_access', 'graduated_by', 'reversed_at', 'reversed_by', 'reversal_reason',
    ];

    protected $casts = [
        'graduated_at' => 'date',
        'final_average' => 'decimal:2',
        'outstanding_balance' => 'decimal:2',
        'reversed_at' => 'datetime',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function graduatedBy(): BelongsTo { return $this->belongsTo(User::class, 'graduated_by'); }
}
