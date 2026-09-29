<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Designation extends Model
{
    protected $fillable = ['school_id', 'name', 'description', 'permissions'];
    protected $casts = ['permissions' => 'array'];

    public function school(): BelongsTo { return $this->belongsTo(School::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
}
