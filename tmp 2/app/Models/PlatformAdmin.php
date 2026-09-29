<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PlatformAdmin extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = ['name','email','password','role','is_active','totp_secret','recovery_codes','totp_confirmed_at','last_totp_hash','last_login_at','last_login_ip'];
    protected $hidden = ['password','remember_token','totp_secret','recovery_codes','last_totp_hash'];

    protected function casts(): array
    {
        return ['password'=>'hashed','is_active'=>'boolean','totp_secret'=>'encrypted','recovery_codes'=>'array','totp_confirmed_at'=>'datetime','last_login_at'=>'datetime'];
    }
}