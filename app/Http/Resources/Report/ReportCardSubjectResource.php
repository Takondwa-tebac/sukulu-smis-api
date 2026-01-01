<?php

namespace App\Http\Resources\Report;

use App\Http\Resources\Subject\SubjectResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ReportCardSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'report_card_id' => $this->report_card_id,
            'subject_id' => $this->subject_id,
            'ca_score' => $this->ca_score ? (float) $this->ca_score : null,
            'exam_score' => $this->exam_score ? (float) $this->exam_score : null,
            'total_score' => $this->total_score ? (float) $this->total_score : null,
            'grade' => $this->grade,
            'position' => $this->position,
            'remarks' => $this->remarks,
            'teacher_initials' => $this->teacher_initials,
            'subject' => new SubjectResource($this->whenLoaded('subject')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
