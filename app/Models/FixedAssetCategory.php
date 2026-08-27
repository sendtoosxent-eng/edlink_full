<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAssetCategory extends Model
{
    protected $fillable = ['school_id', 'code', 'name', 'useful_life_months', 'depreciation_method', 'annual_rate', 'asset_account_id', 'accumulated_depreciation_account_id', 'depreciation_expense_account_id', 'is_active'];

    protected $casts = ['annual_rate' => 'decimal:4', 'is_active' => 'boolean'];

    public function assetAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'asset_account_id');
    }

    public function accumulatedAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'accumulated_depreciation_account_id');
    }

    public function expenseAccount()
    {
        return $this->belongsTo(LedgerAccount::class, 'depreciation_expense_account_id');
    }
}
