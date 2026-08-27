<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingBudget extends Model
{
    protected $fillable = ['school_id', 'fiscal_year_id', 'name', 'status', 'created_by'];

    public function lines()
    {
        return $this->hasMany(AccountingBudgetLine::class);
    }

    public function fiscalYear()
    {
        return $this->belongsTo(FiscalYear::class);
    }
}
