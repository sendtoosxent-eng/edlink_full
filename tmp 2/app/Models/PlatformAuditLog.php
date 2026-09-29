<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlatformAuditLog extends Model
{
    protected $fillable = ['platform_admin_id','event','metadata','ip_address','user_agent'];
    protected $casts = ['metadata'=>'array'];
    public function administrator(): BelongsTo { return $this->belongsTo(PlatformAdmin::class, 'platform_admin_id'); }
}