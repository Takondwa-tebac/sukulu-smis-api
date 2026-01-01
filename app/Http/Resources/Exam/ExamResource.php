<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Http\Resources\AcademicYear\TermResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'academic_year_id' => $this->academic_year_id,
            'term_id' => $this->term_id,
            'exam_type_id' => $this->exam_type_id,
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'max_score' => (float) $this->max_score,
            'status' => $this->status,
            'instructions' => $this->instructions,
            'settings' => $this->settings,
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'term' => new TermResource($this->whenLoaded('term')),
            'exam_type' => new ExamTypeResource($this->whenLoaded('examType')),
            'exam_subjects' => ExamSubjectResource::collection($this->whenLoaded('examSubjects')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
