<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DemoRegistration extends Model
{
    protected $fillable = ['email', 'used_at'];

    protected $casts = [
        'used_at' => 'datetime',
    ];
}
