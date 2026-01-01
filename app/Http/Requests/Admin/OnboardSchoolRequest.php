<?php

namespace App\Http\Requests\Admin;

use App\Models\School;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OnboardSchoolRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // School details
            'school_name' => ['required', 'string', 'max:255'],
            'school_type' => ['required', Rule::in(School::getSchoolTypes())],
            'school_address' => ['nullable', 'string', 'max:500'],
            'school_city' => ['nullable', 'string', 'max:100'],
            'school_region' => ['nullable', 'string', 'max:100'],
            'school_country' => ['nullable', 'string', 'max:100'],
            'school_phone' => ['nullable', 'string', 'max:20'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'school_website' => ['nullable', 'url', 'max:255'],
            'school_motto' => ['nullable', 'string', 'max:255'],
            'school_established_year' => ['nullable', 'integer', 'min:1800', 'max:' . date('Y')],
            'school_registration_number' => ['nullable', 'string', 'max:100'],

            // Admin user details
            'admin_name' => ['required', 'string', 'max:255'],
            'admin_email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'admin_phone' => ['nullable', 'string', 'max:20'],
            'admin_password' => ['nullable', 'string', 'min:8'],

            // Subscription
            'subscription_plan' => ['nullable', 'string', 'in:free,basic,premium,enterprise'],
            'subscription_months' => ['nullable', 'integer', 'min:1', 'max:36'],

            // Modules to enable
            'enabled_modules' => ['nullable', 'array'],
            'enabled_modules.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'admin_email.unique' => 'A user with this email already exists.',
        ];
    }
}
