<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeworkSubmission extends Model
{
    protected $hidden = ['attachment_path'];
    protected $fillable = ['homework_assignment_id','student_id','submitted_by','answer','attachment_path','attachment_name','submitted_at','status','score','feedback','reviewed_at'];
    protected $casts = ['submitted_at'=>'datetime','reviewed_at'=>'datetime','score'=>'decimal:2'];

    public function assignment(): BelongsTo { return $this->belongsTo(HomeworkAssignment::class, 'homework_assignment_id'); }
    public function student(): BelongsTo { return $this->belongsTo(Student::class); }
    public function submittedBy(): BelongsTo { return $this->belongsTo(User::class, 'submitted_by'); }
}
