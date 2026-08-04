<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PrivacyRequest extends Model { protected $fillable=['school_id','requested_by','subject_type','subject_id','request_type','status','reason','verification_token_hash','verified_at','completed_at','result']; protected $hidden=['verification_token_hash']; protected $casts=['verified_at'=>'datetime','completed_at'=>'datetime','result'=>'array']; }
