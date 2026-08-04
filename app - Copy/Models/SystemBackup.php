<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class SystemBackup extends Model { protected $fillable=['disk','path','size','checksum','status','verified_at','restored_tested_at','failure']; protected $casts=['verified_at'=>'datetime','restored_tested_at'=>'datetime']; }
