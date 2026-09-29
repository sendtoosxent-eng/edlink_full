<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceLedgerEntry extends Model {
    protected $fillable=['school_id','term_id','financial_account_id','reference','source_type','source_id','entry_type','direction','amount','description','status','recorded_by','approved_by','approved_at','reversal_of_id','reversal_reason','posted_at'];
    protected $casts=['amount'=>'decimal:2','approved_at'=>'datetime','posted_at'=>'datetime'];
}
