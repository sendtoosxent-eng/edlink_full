<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;
class SchoolEvent extends Model {protected $fillable=['school_id','term_id','title','event_date','type','target_audience','description'];protected $casts=['event_date'=>'date'];public function term():BelongsTo{return $this->belongsTo(Term::class);}}
