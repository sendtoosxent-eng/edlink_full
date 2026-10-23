<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    protected $fillable = ['school_id', 'term_id', 'exam_category_id', 'school_class_id', 'stream_id', 'name', 'status', 'completed_at', 'published_at', 'published_by'];

    protected $casts = ['published_at' => 'datetime', 'completed_at' => 'datetime'];

    public function term(): BelongsTo
    {
        return $this->belongsTo(Term::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ExamCategory::class, 'exam_category_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }

    public function stream(): BelongsTo
    {
        return $this->belongsTo(Stream::class);
    }

    public function papers(): HasMany
    {
        return $this->hasMany(ExamPaper::class);
    }

    public function publisher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'published_by');
    }

    public function isPublished(): bool
    {
        return $this->published_at !== null;
    }

    public function scopeWithResultsForStudent(Builder $query, int $studentId): Builder
    {
        return $query->whereExists(function ($marks) use ($studentId): void {
            $marks->selectRaw('1')->from('exam_marks')
                ->join('exam_papers', 'exam_papers.id', '=', 'exam_marks.exam_paper_id')
                ->join('exam_paper_submissions', 'exam_paper_submissions.exam_paper_id', '=', 'exam_papers.id')
                ->whereColumn('exam_papers.exam_id', 'exams.id')
                ->where('exam_marks.student_id', $studentId)
                ->whereNotNull('exam_marks.score')
                ->where('exam_paper_submissions.status', 'approved');
        });
    }
}
