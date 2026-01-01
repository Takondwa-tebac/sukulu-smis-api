<?php

namespace App\Http\Requests\SchoolClass;

use App\Models\SchoolClass;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSchoolClassRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('classes')->where('school_id', $this->user()->school_id)->ignore($this->route('class'))],
            'level' => ['sometimes', 'required', Rule::in(SchoolClass::getLevels())],
            'grade_number' => ['sometimes', 'required', 'integer', 'min:1'],
            'capacity' => ['nullable', 'integer', 'min:1'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
