<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class ChatMessage extends Model
{
    protected $fillable = ['school_id', 'sender_id', 'recipient_id', 'body', 'client_token'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime'];
    }

    public function scopeBetween(Builder $query, int $schoolId, int $userId, int $peerId): Builder
    {
        return $query->where('school_id', $schoolId)->where(function (Builder $query) use ($userId, $peerId) {
            $query->where(fn ($q) => $q->where('sender_id', $userId)->where('recipient_id', $peerId))
                ->orWhere(fn ($q) => $q->where('sender_id', $peerId)->where('recipient_id', $userId));
        });
    }
}
