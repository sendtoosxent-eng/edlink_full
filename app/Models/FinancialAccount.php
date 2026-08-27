<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class FinancialAccount extends Model
{
    protected $fillable = ['school_id', 'ledger_account_id', 'name', 'type', 'currency', 'opening_balance', 'is_active'];

    protected $casts = ['opening_balance' => 'decimal:2', 'is_active' => 'boolean'];

    public function ledgerAccount()
    {
        return $this->belongsTo(LedgerAccount::class);
    }

    public function balance(): float
    {
        return (float) $this->opening_balance + (float) CashPoolEntry::where('financial_account_id', $this->id)->sum(DB::raw("case when direction='credit' then amount else -amount end"));
    }

    public static function ensureDefaults(School $school): void
    {
        $currency = (string) SchoolSetting::getValue($school->id, 'currency', 'UGX');
        foreach ([['Cash on Hand', 'cash'], ['Main Bank Account', 'bank'], ['Mobile Money', 'mobile_money'], ['Petty Cash', 'petty_cash']] as [$name,$type]) {
            static::firstOrCreate(['school_id' => $school->id, 'type' => $type], ['name' => $name, 'currency' => $currency, 'opening_balance' => 0, 'is_active' => true]);
        }
    }
}
