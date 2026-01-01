<?php

namespace App\Http\Requests\Report;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'class_teacher_remarks' => ['nullable', 'string', 'max:1000'],
            'head_teacher_remarks' => ['nullable', 'string', 'max:1000'],
            'next_term_begins' => ['nullable', 'date'],
            'next_term_fees' => ['nullable', 'numeric', 'min:0'],
        ];
    }
}
