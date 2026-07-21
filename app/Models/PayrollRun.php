<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class PayrollRun extends Model { protected $fillable=['school_id','term_id','period','user_id','amount','paid_at','recorded_by']; protected $casts=['amount'=>'decimal:2','paid_at'=>'datetime']; protected static function booted():void{static::created(fn(self $run)=>AuditLog::record($run->school_id,'payroll.recorded',$run,['amount'=>$run->amount,'staff_id'=>$run->user_id,'period'=>$run->period,'term_id'=>$run->term_id]));} public function staff():BelongsTo{return $this->belongsTo(User::class,'user_id');} public function term():BelongsTo{return $this->belongsTo(Term::class);} }
