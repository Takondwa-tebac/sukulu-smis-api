<?php

namespace App\Http\Resources\Report;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Http\Resources\AcademicYear\TermResource;
use App\Http\Resources\SchoolClass\SchoolClassResource;
use App\Http\Resources\SchoolClass\StreamResource;
use App\Http\Resources\Student\StudentResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'student_id' => $this->student_id,
            'academic_year_id' => $this->academic_year_id,
            'term_id' => $this->term_id,
            'class_id' => $this->class_id,
            'stream_id' => $this->stream_id,
            'total_score' => $this->total_score ? (float) $this->total_score : null,
            'average_score' => $this->average_score ? (float) $this->average_score : null,
            'position' => $this->position,
            'total_students' => $this->total_students,
            'overall_grade' => $this->overall_grade,
            'class_teacher_remarks' => $this->class_teacher_remarks,
            'head_teacher_remarks' => $this->head_teacher_remarks,
            'next_term_begins' => $this->next_term_begins?->toDateString(),
            'next_term_fees' => $this->next_term_fees ? (float) $this->next_term_fees : null,
            'status' => $this->status,
            'is_published' => $this->isPublished(),
            'approved_at' => $this->approved_at?->toISOString(),
            'published_at' => $this->published_at?->toISOString(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'term' => new TermResource($this->whenLoaded('term')),
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'stream' => new StreamResource($this->whenLoaded('stream')),
            'subjects' => ReportCardSubjectResource::collection($this->whenLoaded('subjects')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
