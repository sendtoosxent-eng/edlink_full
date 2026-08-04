<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SchoolGroup extends Model
{
    protected $fillable = ['name', 'code', 'status'];

    public function schools(): HasMany
    {
        return $this->hasMany(School::class);
    }
}
