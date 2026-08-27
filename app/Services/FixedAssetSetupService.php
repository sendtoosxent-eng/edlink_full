<?php

namespace App\Services;

use App\Models\FixedAssetCategory;
use App\Models\LedgerAccount;
use App\Models\School;

class FixedAssetSetupService
{
    public function activate(School $school): void
    {
        $accounts = LedgerAccount::where('school_id', $school->id)->pluck('id', 'code');
        foreach ([['LAND', 'Land', 1, 'none', null, '1510'], ['BLDG', 'Buildings', 360, 'straight_line', null, '1520'], ['FURN', 'Furniture and Equipment', 60, 'straight_line', null, '1530'], ['ICT', 'Computers and ICT Equipment', 36, 'straight_line', null, '1540'], ['VEH', 'Vehicles', 60, 'reducing_balance', 20, '1550']] as [$code, $name, $life, $method, $rate, $assetCode]) {
            FixedAssetCategory::firstOrCreate(['school_id' => $school->id, 'code' => $code], ['name' => $name, 'useful_life_months' => $life, 'depreciation_method' => $method, 'annual_rate' => $rate, 'asset_account_id' => $accounts[$assetCode], 'accumulated_depreciation_account_id' => $accounts['1590'], 'depreciation_expense_account_id' => $accounts['5600'], 'is_active' => true]);
        }
    }
}
