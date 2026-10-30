<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SchoolWhatsappConfiguration extends Model
{
    protected $guarded = ['id'];

    protected $hidden = ['access_token', 'app_secret', 'verify_token'];

    protected $casts = ['enabled' => 'boolean', 'access_token' => 'encrypted', 'app_secret' => 'encrypted', 'verify_token' => 'encrypted', 'templates' => 'array', 'connected_at' => 'datetime'];

    public function isReady(): bool
    {
        return $this->enabled && $this->connected_at && filled($this->access_token) && filled($this->app_secret) && filled($this->phone_number_id);
    }
}
