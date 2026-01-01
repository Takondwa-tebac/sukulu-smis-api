<?php

namespace App\Http\Requests\Exam;

use App\Models\Exam;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateExamRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_score' => ['nullable', 'numeric', 'min:1'],
            'status' => ['nullable', Rule::in(Exam::getStatuses())],
            'instructions' => ['nullable', 'string', 'max:5000'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
