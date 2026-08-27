<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FixedAssetDepreciation extends Model
{
    protected $fillable = ['school_id', 'fixed_asset_id', 'journal_id', 'period_ending', 'opening_carrying_value', 'depreciation_amount', 'closing_carrying_value', 'status', 'prepared_by', 'approved_by', 'posted_at'];

    protected $casts = ['period_ending' => 'date', 'opening_carrying_value' => 'decimal:2', 'depreciation_amount' => 'decimal:2', 'closing_carrying_value' => 'decimal:2', 'posted_at' => 'datetime'];

    public function asset()
    {
        return $this->belongsTo(FixedAsset::class, 'fixed_asset_id');
    }

    public function journal()
    {
        return $this->belongsTo(AccountingJournal::class);
    }
}
