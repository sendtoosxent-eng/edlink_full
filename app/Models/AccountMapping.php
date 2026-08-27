<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccountMapping extends Model
{
    protected $fillable = ['school_id', 'mapping_type', 'source_type', 'source_id', 'ledger_account_id', 'metadata', 'updated_by'];

    protected $casts = ['metadata' => 'array'];

    public function account()
    {
        return $this->belongsTo(LedgerAccount::class, 'ledger_account_id');
    }
}
