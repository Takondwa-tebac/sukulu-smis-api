<?php

namespace App\Http\Resources\Attendance;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Http\Resources\AcademicYear\TermResource;
use App\Http\Resources\SchoolClass\SchoolClassResource;
use App\Http\Resources\SchoolClass\StreamResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AttendanceSessionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'academic_year_id' => $this->academic_year_id,
            'term_id' => $this->term_id,
            'class_id' => $this->class_id,
            'stream_id' => $this->stream_id,
            'date' => $this->date?->toDateString(),
            'session_type' => $this->session_type,
            'taken_at' => $this->taken_at?->toISOString(),
            'notes' => $this->notes,
            'status' => $this->status,
            'summary' => $this->when($this->relationLoaded('attendances'), fn () => $this->getAttendanceSummary()),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'term' => new TermResource($this->whenLoaded('term')),
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'stream' => new StreamResource($this->whenLoaded('stream')),
            'taken_by' => new UserResource($this->whenLoaded('takenByUser')),
            'attendances' => StudentAttendanceResource::collection($this->whenLoaded('attendances')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
