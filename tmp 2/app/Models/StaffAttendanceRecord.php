<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffAttendanceRecord extends Model
{
    public const STATUSES = ['present', 'absent', 'late', 'excused', 'on_leave'];

    protected $fillable = ['school_id', 'user_id', 'attendance_date', 'status', 'note', 'recorded_by'];
    protected $casts = ['attendance_date' => 'date'];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function staff(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}