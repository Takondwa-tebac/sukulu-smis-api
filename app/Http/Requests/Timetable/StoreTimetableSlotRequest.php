<?php

namespace App\Http\Requests\Timetable;

use App\Models\TimetableSlot;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreTimetableSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'slots' => ['required', 'array', 'min:1'],
            'slots.*.time_period_id' => ['required', 'uuid', 'exists:time_periods,id'],
            'slots.*.day_of_week' => ['required', Rule::in(TimetableSlot::getDays())],
            'slots.*.class_subject_id' => ['nullable', 'uuid', 'exists:class_subjects,id'],
            'slots.*.teacher_id' => ['nullable', 'uuid', 'exists:users,id'],
            'slots.*.room' => ['nullable', 'string', 'max:100'],
            'slots.*.notes' => ['nullable', 'string', 'max:500'],
        ];
    }
}
