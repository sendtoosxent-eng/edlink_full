<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PublicVisitor extends Model
{
    public $incrementing=false;
    protected $keyType='string';
    protected $guarded=[];
    protected $hidden=['ip_address'];
    protected $casts=['ip_address'=>'encrypted','consented_at'=>'datetime','last_seen_at'=>'datetime'];
    public function enquiries() { return $this->hasMany(PublicEnquiry::class); }
    public function visits() { return $this->hasMany(PublicVisit::class); }
}
