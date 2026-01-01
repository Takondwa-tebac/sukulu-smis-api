<?php

namespace App\Http\Requests\Notification;

use App\Models\NotificationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreNotificationTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:100', 'unique:notification_templates,code'],
            'type' => ['required', Rule::in(NotificationTemplate::getTypes())],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'variables' => ['nullable', 'array'],
            'is_active' => ['boolean'],
        ];
    }
}
