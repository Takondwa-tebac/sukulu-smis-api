<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAdmissionPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'start_date' => ['sometimes', 'required', 'date'],
            'end_date' => ['sometimes', 'required', 'date', 'after:start_date'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'max_applications' => ['nullable', 'integer', 'min:1'],
            'requirements' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
