<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PublicEnquiry extends Model
{
    public const CONSENT_VERSION='public-enquiries-v1';
    public const CONSENT_TEXT='I agree that the Edlink support team may contact me about this request using my selected contact method.';
    protected $guarded=[];
    protected $casts=['followup_consent'=>'boolean','consented_at'=>'datetime','closed_at'=>'datetime','opted_out_at'=>'datetime'];
    public function visitor() { return $this->belongsTo(PublicVisitor::class,'public_visitor_id'); }
}
