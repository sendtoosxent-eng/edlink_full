<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class MarksUpdateRequest extends FormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'marks' => ['required', 'array'],
            'marks.*.student_id' => ['required', 'integer'],
            'marks.*.score' => ['nullable', 'numeric', 'min:0'],
            'base_version' => ['nullable', 'date'],
        ];
    }
}
