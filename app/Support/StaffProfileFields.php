<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class StaffProfileFields
{
    public const TITLES = ['Sir', 'Mr', 'Miss', 'Ms', 'Mrs', 'Dr', 'Prof', 'Rev', 'Fr', 'Sr', 'Eng'];

    public static function rules(): array
    {
        return [
            'title' => ['nullable', Rule::in(self::TITLES)],
            'date_of_birth' => ['nullable', 'date', 'before_or_equal:today'],
            'gender' => ['nullable', Rule::in(['Female', 'Male', 'Other', 'Prefer not to say'])],
            'nationality' => ['nullable', 'string', 'max:100'],
            'teaching_license_number' => ['nullable', 'string', 'max:100'],
            'academic_qualifications' => ['nullable', 'string', 'max:5000'],
            'qualification_institution' => ['nullable', 'string', 'max:255'],
            'qualification_year' => ['nullable', 'integer', 'min:1900', 'max:'.now()->year],
        ];
    }
}
