<?php

namespace App\Http\Requests\GradingSystem;

use Illuminate\Foundation\Http\FormRequest;

class CalculateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'grading_system_id' => ['required', 'uuid', 'exists:grading_systems,id'],
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.subject_code' => ['required', 'string', 'max:10'],
            'scores.*.score' => ['required', 'numeric', 'min:0'],
        ];
    }
}
