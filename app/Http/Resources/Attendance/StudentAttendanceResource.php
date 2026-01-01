<?php

namespace App\Http\Resources\Attendance;

use App\Http\Resources\Student\StudentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentAttendanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'attendance_session_id' => $this->attendance_session_id,
            'student_id' => $this->student_id,
            'status' => $this->status,
            'arrival_time' => $this->arrival_time?->format('H:i'),
            'departure_time' => $this->departure_time?->format('H:i'),
            'absence_reason' => $this->absence_reason,
            'notes' => $this->notes,
            'is_present' => $this->isPresent(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
