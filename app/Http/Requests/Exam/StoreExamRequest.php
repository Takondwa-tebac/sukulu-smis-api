<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
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
            'exam_type_id' => ['required', 'uuid', 'exists:exam_types,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_score' => ['nullable', 'numeric', 'min:1'],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'settings' => ['nullable', 'array'],
            'subjects' => ['nullable', 'array'],
            'subjects.*.class_subject_id' => ['required_with:subjects', 'uuid', 'exists:class_subjects,id'],
            'subjects.*.exam_date' => ['nullable', 'date'],
            'subjects.*.start_time' => ['nullable', 'date_format:H:i'],
            'subjects.*.duration_minutes' => ['nullable', 'integer', 'min:1'],
            'subjects.*.max_score' => ['nullable', 'numeric', 'min:1'],
            'subjects.*.venue' => ['nullable', 'string', 'max:255'],
        ];
    }
}
