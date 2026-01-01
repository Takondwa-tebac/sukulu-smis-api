<?php

namespace App\Http\Requests\GradingSystem;

use App\Models\GradingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGradingSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        $gradingSystemId = $this->route('grading_system')->id;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['sometimes', 'required', 'string', 'max:50', Rule::unique('grading_systems')->ignore($gradingSystemId)],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['sometimes', 'required', Rule::in(GradingSystem::getTypes())],
            'scale_type' => ['sometimes', 'required', Rule::in(GradingSystem::getScaleTypes())],
            'min_score' => ['sometimes', 'required', 'numeric', 'min:0'],
            'max_score' => ['sometimes', 'required', 'numeric', 'gt:min_score'],
            'pass_mark' => ['sometimes', 'required', 'numeric', 'min:0'],
            'min_subjects_to_pass' => ['sometimes', 'required', 'integer', 'min:1'],
            'priority_subjects' => ['nullable', 'array'],
            'priority_subjects.*' => ['string', 'max:10'],
            'certification_rules' => ['nullable', 'array'],
            'progression_rules' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
