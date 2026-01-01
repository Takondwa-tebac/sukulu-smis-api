<?php

namespace App\Http\Resources\Timetable;

use App\Http\Resources\Subject\ClassSubjectResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableSlotResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'timetable_id' => $this->timetable_id,
            'time_period_id' => $this->time_period_id,
            'day_of_week' => $this->day_of_week,
            'class_subject_id' => $this->class_subject_id,
            'teacher_id' => $this->teacher_id,
            'room' => $this->room,
            'notes' => $this->notes,
            'time_period' => new TimePeriodResource($this->whenLoaded('timePeriod')),
            'class_subject' => new ClassSubjectResource($this->whenLoaded('classSubject')),
            'teacher' => new UserResource($this->whenLoaded('teacher')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
