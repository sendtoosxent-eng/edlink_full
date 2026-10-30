<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappDelivery extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['status_at' => 'datetime'];

    public function broadcast()
    {
        return $this->belongsTo(WhatsappBroadcast::class, 'whatsapp_broadcast_id');
    }

    public function contact()
    {
        return $this->belongsTo(WhatsappContact::class, 'whatsapp_contact_id');
    }
}
