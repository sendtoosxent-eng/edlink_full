<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SchoolSetting extends Model
{
    protected $fillable = ['school_id', 'key', 'value'];

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public static function getValue(int $schoolId, string $key, mixed $default = null): mixed
    {
        $setting = static::query()
            ->where('school_id', $schoolId)
            ->where('key', $key)
            ->first(['value']);

        return $setting?->value ?? $default;
    }

    public static function setValue(int $schoolId, string $key, mixed $value): self
    {
        return static::updateOrCreate(
            ['school_id' => $schoolId, 'key' => $key],
            ['value' => $value],
        );
    }
}
