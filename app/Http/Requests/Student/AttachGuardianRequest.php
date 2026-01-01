<?php

namespace App\Http\Requests\Student;

use Illuminate\Foundation\Http\FormRequest;

class AttachGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'guardian_id' => ['required', 'uuid', 'exists:guardians,id'],
            'relationship' => ['required', 'string', 'max:50'],
            'is_primary' => ['boolean'],
            'is_emergency_contact' => ['boolean'],
        ];
    }
}
