<?php

namespace App\Http\Requests\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentMarksRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $examSubject = $this->route('exam_subject');
        $maxScore = $examSubject ? $examSubject->max_score : 100;

        return [
            'marks' => ['required', 'array', 'min:1'],
            'marks.*.student_id' => ['required', 'uuid', 'exists:students,id'],
            'marks.*.score' => ['nullable', 'numeric', 'min:0', 'max:' . $maxScore],
            'marks.*.is_absent' => ['boolean'],
            'marks.*.absent_reason' => ['nullable', 'string', 'max:255'],
            'marks.*.remarks' => ['nullable', 'string', 'max:500'],
        ];
    }
}
