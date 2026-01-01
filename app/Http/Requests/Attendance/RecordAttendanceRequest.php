<?php

namespace App\Http\Requests\Attendance;

use App\Models\StudentAttendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendances' => ['required', 'array', 'min:1'],
            'attendances.*.student_id' => ['required', 'uuid', 'exists:students,id'],
            'attendances.*.status' => ['required', Rule::in(StudentAttendance::getStatuses())],
            'attendances.*.arrival_time' => ['nullable', 'date_format:H:i'],
            'attendances.*.departure_time' => ['nullable', 'date_format:H:i'],
            'attendances.*.absence_reason' => ['nullable', 'string', 'max:255'],
            'attendances.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
