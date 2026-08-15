<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HomeworkAssignment extends Model
{
    protected $hidden = ['attachment_path'];
    protected $fillable = ['school_id','term_id','teacher_id','school_class_id','stream_id','subject_id','title','instructions','attachment_path','attachment_name','maximum_score','due_at','published_at'];
    protected $casts = ['due_at'=>'datetime','published_at'=>'datetime'];

    public function teacher(): BelongsTo { return $this->belongsTo(User::class, 'teacher_id'); }
    public function term(): BelongsTo { return $this->belongsTo(Term::class); }
    public function schoolClass(): BelongsTo { return $this->belongsTo(SchoolClass::class); }
    public function stream(): BelongsTo { return $this->belongsTo(Stream::class); }
    public function subject(): BelongsTo { return $this->belongsTo(Subject::class); }
    public function submissions(): HasMany { return $this->hasMany(HomeworkSubmission::class); }
}
