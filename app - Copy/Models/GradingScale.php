<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;class GradingScale extends Model{protected $fillable=['school_id','education_stage','minimum_percentage','maximum_percentage','grade','aggregate_points','remark'];protected $casts=['minimum_percentage'=>'decimal:2','maximum_percentage'=>'decimal:2','aggregate_points'=>'integer'];}
