<?php

namespace App\Http\Resources\Subject;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Http\Resources\SchoolClass\SchoolClassResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ClassSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'class_id' => $this->class_id,
            'subject_id' => $this->subject_id,
            'academic_year_id' => $this->academic_year_id,
            'is_compulsory' => $this->is_compulsory,
            'periods_per_week' => $this->periods_per_week,
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
