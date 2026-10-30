<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappBroadcast extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['parameters' => 'array'];

    public function deliveries()
    {
        return $this->hasMany(WhatsappDelivery::class);
    }
}
