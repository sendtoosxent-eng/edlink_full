<?php

namespace App\Http\Requests\Api;

use App\Models\AttendanceRecord;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AttendanceStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'school_class_id' => ['required', 'integer'],
            'subject_id' => ['nullable', 'integer'],
            'attendance_date' => ['required', 'date'],
            'session_key' => ['required', 'string', 'max:80'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_id' => ['required', 'integer'],
            'records.*.status' => ['required', Rule::in(AttendanceRecord::STATUSES)],
            'base_version' => ['nullable', 'date'],
        ];
    }
}
