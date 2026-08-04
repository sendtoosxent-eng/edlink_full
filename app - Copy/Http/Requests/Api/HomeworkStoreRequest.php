<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class HomeworkStoreRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'school_class_id' => ['required', 'integer'],
            'stream_id' => ['nullable', 'integer'],
            'subject_id' => ['required', 'integer'],
            'title' => ['required', 'string', 'max:160'],
            'instructions' => ['required', 'string', 'max:10000'],
            'maximum_score' => ['required', 'integer', 'min:1', 'max:1000'],
            'due_at' => ['required', 'date', 'after:now'],
        ];
    }
}
