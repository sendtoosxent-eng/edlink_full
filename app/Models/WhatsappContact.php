<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WhatsappContact extends Model
{
    protected $guarded = ['id'];

    protected $casts = ['consented_at' => 'datetime', 'opted_out_at' => 'datetime'];

    public function canReceive(): bool
    {
        return $this->consented_at !== null && $this->opted_out_at === null;
    }
}
