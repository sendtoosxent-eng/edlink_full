<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentConduct extends Model
{
    protected $fillable = ['school_id', 'student_id', 'occurred_on', 'kind', 'description', 'action', 'recorded_by', 'updated_by'];

    protected $casts = ['occurred_on' => 'date'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function recordedBy(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
    public function updatedBy(): BelongsTo { return $this->belongsTo(User::class, 'updated_by'); }
}
