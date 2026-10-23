<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeaveHandover extends Model
{
    protected $guarded = ['id'];
    protected $casts = ['class_ids' => 'array', 'subject_assignments' => 'array', 'timetable_slots' => 'array', 'temporary' => 'boolean'];

    public function toUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'to_user_id');
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function leave(): BelongsTo
    {
        return $this->belongsTo(StaffLeave::class, 'staff_leave_id');
    }
}
