<?php

namespace App\Http\Resources\Student;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Http\Resources\SchoolClass\SchoolClassResource;
use App\Http\Resources\SchoolClass\StreamResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentEnrollmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'class_id' => $this->class_id,
            'stream_id' => $this->stream_id,
            'enrollment_date' => $this->enrollment_date?->toDateString(),
            'status' => $this->status,
            'student' => new StudentResource($this->whenLoaded('student')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'stream' => new StreamResource($this->whenLoaded('stream')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
