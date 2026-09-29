<?php
namespace App\Models;use Illuminate\Database\Eloquent\Model;use Illuminate\Database\Eloquent\Relations\BelongsTo;class ExamPaper extends Model{protected $fillable=['exam_id','subject_id','maximum_score','weighting'];public function exam():BelongsTo{return $this->belongsTo(Exam::class);}public function subject():BelongsTo{return $this->belongsTo(Subject::class);}}
