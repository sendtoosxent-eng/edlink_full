<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSmsConfiguration extends Model
{
    protected $fillable = [
        'school_id', 'enabled', 'provider', 'api_key', 'api_username',
        'sender_id', 'endpoint', 'webhook_secret',
    ];

    protected $hidden = ['api_key', 'webhook_secret'];

    protected function casts(): array
    {
        return [
            'enabled' => 'boolean',
            'api_key' => 'encrypted',
            'webhook_secret' => 'encrypted',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function isReady(): bool
    {
        return $this->enabled
            && filled($this->api_key)
            && filled($this->sender_id)
            && ($this->provider !== 'africastalking' || filled($this->api_username))
            && ($this->provider !== 'custom' || filled($this->endpoint));
    }
}
