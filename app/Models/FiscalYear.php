<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FiscalYear extends Model
{
    protected $fillable = ['school_id', 'name', 'starts_on', 'ends_on', 'status', 'closed_at', 'closed_by'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'closed_at' => 'datetime'];

    public function periods()
    {
        return $this->hasMany(AccountingPeriod::class);
    }
}
