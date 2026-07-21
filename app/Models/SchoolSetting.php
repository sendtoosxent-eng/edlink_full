<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SchoolSetting extends Model {protected $fillable=['school_id','key','value'];public function school():BelongsTo{return $this->belongsTo(School::class);} }
