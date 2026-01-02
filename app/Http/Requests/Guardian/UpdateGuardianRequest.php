<?php

namespace App\Http\Requests\Guardian;

use App\Models\Guardian;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGuardianRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:100'],
            'middle_name' => ['nullable', 'string', 'max:100'],
            'last_name' => ['sometimes', 'required', 'string', 'max:100'],
            'relationship_type' => ['sometimes', Rule::in(Guardian::getRelationshipTypes())],
            // Accept both phone_primary (db) and phone_number (frontend)
            'phone_primary' => ['sometimes', 'string', 'max:20'],
            'phone_number' => ['sometimes', 'string', 'max:20'],
            // Accept both phone_secondary (db) and alternative_phone (frontend)
            'phone_secondary' => ['nullable', 'string', 'max:20'],
            'alternative_phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'occupation' => ['nullable', 'string', 'max:100'],
            'employer' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string', 'max:500'],
            'national_id' => ['nullable', 'string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }

    /**
     * Prepare data for validation - map frontend field names to database columns.
     */
    protected function prepareForValidation(): void
    {
        if ($this->has('phone_number') && !$this->has('phone_primary')) {
            $this->merge(['phone_primary' => $this->phone_number]);
        }
        if ($this->has('alternative_phone') && !$this->has('phone_secondary')) {
            $this->merge(['phone_secondary' => $this->alternative_phone]);
        }
    }

    /**
     * Get validated data with proper field mapping.
     */
    public function validated($key = null, $default = null): array
    {
        $validated = parent::validated($key, $default);
        
        if (isset($validated['phone_number'])) {
            $validated['phone_primary'] = $validated['phone_number'];
            unset($validated['phone_number']);
        }
        if (isset($validated['alternative_phone'])) {
            $validated['phone_secondary'] = $validated['alternative_phone'];
            unset($validated['alternative_phone']);
        }
        
        return $validated;
    }
}
