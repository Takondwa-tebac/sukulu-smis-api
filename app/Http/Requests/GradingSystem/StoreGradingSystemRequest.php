<?php

namespace App\Http\Requests\GradingSystem;

use App\Models\GradingSystem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradingSystemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled by middleware
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', 'unique:grading_systems,code'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(GradingSystem::getTypes())],
            'scale_type' => ['required', Rule::in(GradingSystem::getScaleTypes())],
            'min_score' => ['required', 'numeric', 'min:0'],
            'max_score' => ['required', 'numeric', 'gt:min_score'],
            'pass_mark' => ['required', 'numeric', 'min:0', 'lte:max_score'],
            'min_subjects_to_pass' => ['required', 'integer', 'min:1'],
            'priority_subjects' => ['nullable', 'array'],
            'priority_subjects.*' => ['string', 'max:10'],
            'certification_rules' => ['nullable', 'array'],
            'progression_rules' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
            'is_active' => ['boolean'],
            'grade_scales' => ['required', 'array', 'min:1'],
            'grade_scales.*.grade' => ['required', 'string', 'max:10'],
            'grade_scales.*.grade_label' => ['nullable', 'string', 'max:50'],
            'grade_scales.*.min_score' => ['required', 'numeric'],
            'grade_scales.*.max_score' => ['required', 'numeric'],
            'grade_scales.*.gpa_points' => ['nullable', 'numeric', 'min:0'],
            'grade_scales.*.points' => ['nullable', 'integer'],
            'grade_scales.*.remark' => ['nullable', 'string', 'max:100'],
            'grade_scales.*.is_passing' => ['required', 'boolean'],
            'grade_scales.*.sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        return [
            'grade_scales.required' => 'At least one grade scale must be defined.',
            'grade_scales.min' => 'At least one grade scale must be defined.',
            'max_score.gt' => 'Maximum score must be greater than minimum score.',
            'pass_mark.lte' => 'Pass mark cannot exceed maximum score.',
        ];
    }
}
