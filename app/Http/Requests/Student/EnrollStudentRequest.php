<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class EnrollStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
        ];
    }
}
