<?php

namespace App\Http\Requests\Attendance;

use App\Models\AttendanceSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'academic_year_id' => ['required', 'uuid', 'exists:academic_years,id'],
            'term_id' => ['required', 'uuid', 'exists:terms,id'],
            'class_id' => ['required', 'uuid', 'exists:classes,id'],
            'stream_id' => ['nullable', 'uuid', 'exists:streams,id'],
            'date' => ['required', 'date'],
            'session_type' => ['nullable', Rule::in(AttendanceSession::getSessionTypes())],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
