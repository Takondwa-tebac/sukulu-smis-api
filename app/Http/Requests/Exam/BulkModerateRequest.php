<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class BulkModerateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'moderations' => ['required', 'array', 'min:1'],
            'moderations.*.student_mark_id' => ['required', 'uuid', 'exists:student_marks,id'],
            'moderations.*.score' => ['required', 'numeric', 'min:0'],
            'moderations.*.reason' => ['required', 'string', 'max:1000'],
        ];
    }
}
