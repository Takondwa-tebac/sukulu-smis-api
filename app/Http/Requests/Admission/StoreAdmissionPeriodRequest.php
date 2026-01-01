<?php

namespace App\Http\Requests\Admission;

use Illuminate\Foundation\Http\FormRequest;

class StoreAdmissionPeriodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'name' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'application_fee' => ['nullable', 'numeric', 'min:0'],
            'max_applications' => ['nullable', 'integer', 'min:1'],
            'requirements' => ['nullable', 'array'],
            'settings' => ['nullable', 'array'],
        ];
    }
}
