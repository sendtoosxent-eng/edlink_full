<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingFund extends Model
{
    protected $fillable = ['school_id', 'code', 'name', 'is_restricted', 'is_active'];

    protected $casts = ['is_restricted' => 'boolean', 'is_active' => 'boolean'];
}
