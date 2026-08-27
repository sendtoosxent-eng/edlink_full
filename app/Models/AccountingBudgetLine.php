<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountingBudgetLine extends Model
{
    protected $fillable = ['accounting_budget_id', 'ledger_account_id', 'accounting_period_id', 'cost_centre_id', 'fund_id', 'amount'];

    protected $casts = ['amount' => 'decimal:2'];

    public function budget()
    {
        return $this->belongsTo(AccountingBudget::class, 'accounting_budget_id');
    }

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
