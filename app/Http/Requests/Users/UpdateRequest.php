<?php

namespace App\Http\Requests\Users;

use Illuminate\Foundation\Http\FormRequest;

class UpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->route('user')?->id ?? $this->route('id');

        return [
            'school_id' => ['sometimes', 'nullable', 'uuid', 'exists:schools,id'],
            'first_name' => ['sometimes', 'string', 'max:255'],
            'middle_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'initials' => ['sometimes', 'nullable', 'string', 'max:10'],
            'last_name' => ['sometimes', 'string', 'max:255'],
            'username' => ['sometimes', 'string', 'max:255', 'unique:users,username,' . $userId],
            'phone_number' => ['sometimes', 'nullable', 'string', 'max:50'],
            'email' => ['sometimes', 'email', 'max:255', 'unique:users,email,' . $userId],
            'password' => ['sometimes', 'string', 'min:8'],
            'cover_photo' => ['sometimes', 'nullable', 'string', 'max:255'],
            'profile_photo' => ['sometimes', 'nullable', 'string', 'max:255'],
        ];
    }
}
