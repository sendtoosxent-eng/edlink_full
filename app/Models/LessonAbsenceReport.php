<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LessonAbsenceReport extends Model
{
    protected $fillable = ['school_id', 'user_id', 'timetable_slot_id', 'lesson_date', 'lesson_details'];

    protected $casts = ['lesson_date' => 'date', 'lesson_details' => 'array'];
}
