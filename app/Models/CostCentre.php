<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CostCentre extends Model
{
    protected $fillable = ['school_id', 'code', 'name', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
