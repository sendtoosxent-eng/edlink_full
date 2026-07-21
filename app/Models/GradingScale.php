<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class GradingScale extends Model{protected $fillable=['school_id','minimum_percentage','maximum_percentage','grade','remark'];protected $casts=['minimum_percentage'=>'decimal:2','maximum_percentage'=>'decimal:2'];}
