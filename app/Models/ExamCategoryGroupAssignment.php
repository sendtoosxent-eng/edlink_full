<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamCategoryGroupAssignment extends Model
{
    protected $fillable = ['school_id', 'term_id', 'school_class_id', 'exam_category_group_id'];

    public function group(): BelongsTo
    {
        return $this->belongsTo(ExamCategoryGroup::class, 'exam_category_group_id');
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class);
    }
}
