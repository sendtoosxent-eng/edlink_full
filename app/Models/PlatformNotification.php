<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PlatformNotification extends Model
{
    protected $fillable = ['platform_admin_id', 'event_key', 'title', 'message', 'read_at'];
    protected $casts = ['read_at' => 'datetime'];
}
