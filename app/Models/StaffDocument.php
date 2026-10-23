<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StaffDocument extends Model
{
    protected $fillable = ['school_id', 'name', 'path', 'mime_type', 'size'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
