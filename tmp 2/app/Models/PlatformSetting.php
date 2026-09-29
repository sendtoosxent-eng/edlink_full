<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
class PlatformSetting extends Model { protected $fillable=['key','value']; public static function values():array{return static::pluck('value','key')->all();} }
