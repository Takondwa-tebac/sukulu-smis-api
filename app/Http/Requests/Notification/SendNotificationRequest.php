<?php

namespace App\Http\Requests\Notification;

use App\Models\NotificationTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SendNotificationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'template_id' => ['nullable', 'uuid', 'exists:notification_templates,id'],
            'type' => ['required_without:template_id', Rule::in(NotificationTemplate::getTypes())],
            'subject' => ['nullable', 'string', 'max:255'],
            'body' => ['required_without:template_id', 'string', 'max:5000'],
            'data' => ['nullable', 'array'],
            'recipients' => ['required', 'array', 'min:1'],
            'recipients.*.type' => ['required', 'in:user,student,guardian'],
            'recipients.*.id' => ['required', 'uuid'],
        ];
    }
}
