<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class Announcement extends Model{protected $fillable=['school_id','term_id','created_by','title','message','target_audience','send_email','send_sms','delivery_status','sent_at','recipient_count'];protected $casts=['sent_at'=>'datetime','send_email'=>'boolean','send_sms'=>'boolean'];}
