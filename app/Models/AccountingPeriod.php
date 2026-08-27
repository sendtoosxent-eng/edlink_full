<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingPeriod extends Model
{
    protected $fillable = ['school_id', 'fiscal_year_id', 'name', 'starts_on', 'ends_on', 'status', 'status_reason', 'status_changed_by', 'status_changed_at'];

    protected $casts = ['starts_on' => 'date', 'ends_on' => 'date', 'status_changed_at' => 'datetime'];

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }

    public function acceptsPosting(): bool
    {
        return $this->status === 'open';
    }
}
