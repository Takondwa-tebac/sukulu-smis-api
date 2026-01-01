<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class GenerateReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'term_id' => ['required', 'uuid', 'exists:terms,id'],
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
            'grading_system_id' => ['required', 'uuid', 'exists:grading_systems,id'],
            'student_ids' => ['nullable', 'array'],
            'student_ids.*' => ['uuid', 'exists:students,id'],
        ];
    }
}
