<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentSubjectSelection extends Model
{
    protected $fillable = [
        'school_id',
        'term_id',
        'student_id',
        'subject_id',
        'selection_type',
        'selected_by',
    ];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function selectedBy(): BelongsTo { return $this->belongsTo(User::class, 'selected_by'); }
}
