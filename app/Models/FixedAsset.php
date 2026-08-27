<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAsset extends Model
{
    protected $fillable = ['school_id', 'fixed_asset_category_id', 'financial_account_id', 'custodian_id', 'acquisition_journal_id', 'asset_tag', 'name', 'serial_number', 'location', 'description', 'acquisition_date', 'in_service_date', 'cost', 'residual_value', 'useful_life_months', 'depreciation_method', 'annual_rate', 'settlement_type', 'status', 'recorded_by'];

    protected $casts = ['acquisition_date' => 'date', 'in_service_date' => 'date', 'cost' => 'decimal:2', 'residual_value' => 'decimal:2', 'annual_rate' => 'decimal:4'];

    public function category()
    {
        return $this->belongsTo(FixedAssetCategory::class, 'fixed_asset_category_id');
    }

    public function custodian()
    {
        return $this->belongsTo(User::class, 'custodian_id');
    }

    public function financialAccount()
    {
        return $this->belongsTo(FinancialAccount::class);
    }

    public function acquisitionJournal()
    {
        return $this->belongsTo(AccountingJournal::class, 'acquisition_journal_id');
    }

    public function depreciations()
    {
        return $this->hasMany(FixedAssetDepreciation::class);
    }

    public function accumulatedDepreciation(): float
    {
        return (float) $this->depreciations()->where('status', 'posted')->sum('depreciation_amount');
    }

    public function carryingValue(): float
    {
        return max((float) $this->residual_value, (float) $this->cost - $this->accumulatedDepreciation());
    }
}
