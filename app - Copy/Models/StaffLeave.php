<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffLeave extends Model
{
    protected $fillable = ['school_id', 'user_id', 'type', 'starts_on', 'ends_on', 'status', 'reason', 'approved_by'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date'];

    public function staff(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
