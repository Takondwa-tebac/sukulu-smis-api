<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class AddExamSubjectsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'subjects' => ['required', 'array', 'min:1'],
            'subjects.*.class_subject_id' => ['required', 'uuid', 'exists:class_subjects,id'],
            'subjects.*.exam_date' => ['nullable', 'date'],
            'subjects.*.start_time' => ['nullable', 'date_format:H:i'],
            'subjects.*.duration_minutes' => ['nullable', 'integer', 'min:1'],
            'subjects.*.max_score' => ['nullable', 'numeric', 'min:1'],
            'subjects.*.venue' => ['nullable', 'string', 'max:255'],
        ];
    }
}
