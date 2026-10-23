<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeave extends Model
{
    protected $fillable = ['school_id', 'user_id', 'type', 'starts_on', 'ends_on', 'status', 'reason', 'approved_by', 'returned_at', 'returned_by'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'returned_at' => 'datetime'];

    public function scopeOverlapping($query, $from, $to)
    {
        return $query->where('status', 'approved')->whereNull('returned_at')->whereDate('starts_on', '<=', $to)->whereDate('ends_on', '>=', $from);
    }

    public function handover(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(StaffLeaveHandover::class);
    }

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
