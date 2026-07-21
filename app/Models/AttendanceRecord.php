<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecord extends Model
{
    public const STATUSES = ['present', 'absent', 'late', 'excused'];

    protected $fillable = [
        'school_id', 'term_id', 'student_id', 'subject_id', 'school_class_id',
        'stream_id', 'attendance_date', 'lesson_time', 'session_key', 'status', 'recorded_by',
    ];
    protected $casts = ['attendance_date' => 'date'];

    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
