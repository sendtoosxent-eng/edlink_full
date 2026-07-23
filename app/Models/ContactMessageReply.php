<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class ContactMessageReply extends Model {
    protected $fillable=['contact_message_id','platform_admin_id','subject','message','delivery_status','delivery_error','sent_at'];
    protected $casts=['sent_at'=>'datetime'];
    public function contactMessage(): BelongsTo { return $this->belongsTo(ContactMessage::class); }
    public function administrator(): BelongsTo { return $this->belongsTo(PlatformAdmin::class,'platform_admin_id'); }
}