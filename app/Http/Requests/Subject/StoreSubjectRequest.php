<?php

namespace App\Http\Requests\Subject;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:20', Rule::unique('subjects')->where('school_id', $this->user()->school_id)],
            'category' => ['required', Rule::in(Subject::getCategories())],
            'department_id' => ['nullable', 'uuid', 'exists:departments,id'],
            'description' => ['nullable', 'string', 'max:500'],
            'is_active' => ['boolean'],
            'is_elective' => ['boolean'],
            'credit_hours' => ['nullable', 'integer', 'min:1'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }
}
