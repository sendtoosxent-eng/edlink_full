<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingSupplier extends Model
{
    protected $fillable = ['school_id', 'name', 'email', 'phone', 'tax_number', 'is_active'];

    protected $casts = ['is_active' => 'boolean'];
}
