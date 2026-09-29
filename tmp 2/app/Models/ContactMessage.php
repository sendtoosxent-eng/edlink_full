<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class ContactMessage extends Model {
    protected $fillable=['name','email','subject','message','type','status','read_at','closed_at'];
    protected $casts=['read_at'=>'datetime','closed_at'=>'datetime'];
    public function replies(): HasMany { return $this->hasMany(ContactMessageReply::class); }
}