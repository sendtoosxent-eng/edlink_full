<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class FinanceReconciliation extends Model { protected $fillable=['school_id','financial_account_id','period_ending','statement_balance','ledger_balance','difference','notes','reconciled_by','reconciled_at','status','closed_at','reopened_by','reopen_reason','reopened_at']; protected $casts=['period_ending'=>'date','statement_balance'=>'decimal:2','ledger_balance'=>'decimal:2','difference'=>'decimal:2','reconciled_at'=>'datetime','closed_at'=>'datetime','reopened_at'=>'datetime']; public function account(){return $this->belongsTo(FinancialAccount::class,'financial_account_id');} }

