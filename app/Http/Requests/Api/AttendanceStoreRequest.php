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
            'records.*.note' => ['nullable', 'string', 'max:1000'],
            'base_version' => ['nullable', 'date'],
        ];
    }

    public function after(): array
    {
        return [function ($validator) {
            foreach ((array) $this->input('records', []) as $index => $record) {
                if (! is_array($record)) continue;
                $status = $record['status'] ?? '';
                $note = $record['note'] ?? null;
                if (in_array($status, ['absent', 'late', 'excused'], true) && (! is_string($note) || trim($note) === '')) {
                    $validator->errors()->add("records.$index.note", 'Add a reason or the number of minutes late.');
                } elseif ($status === 'late' && (! is_string($note) || ! ctype_digit($note) || (int) $note < 1 || (int) $note > 1440)) {
                    $validator->errors()->add("records.$index.note", 'Minutes late must be a whole number between 1 and 1440.');
                }
            }
        }];
    }
}
