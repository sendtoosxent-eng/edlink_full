<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class AuditLog extends Model
{
    protected $fillable = ['school_id','user_id','event','subject_type','subject_id','metadata','ip_address'];
    protected $casts = ['metadata' => 'array'];

    public static function record(int $schoolId, string $event, ?Model $subject = null, array $metadata = []): void
    {
        static::create(['school_id'=>$schoolId,'user_id'=>Auth::id(),'event'=>$event,'subject_type'=>$subject ? $subject::class : null,'subject_id'=>$subject?->getKey(),'metadata'=>$metadata ?: null,'ip_address'=>request()?->ip()]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
