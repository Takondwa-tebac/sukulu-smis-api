<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\Subject\ClassSubjectResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ExamSubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'exam_id' => $this->exam_id,
            'class_subject_id' => $this->class_subject_id,
            'exam_date' => $this->exam_date?->toDateString(),
            'start_time' => $this->start_time?->format('H:i'),
            'duration_minutes' => $this->duration_minutes,
            'max_score' => (float) $this->max_score,
            'venue' => $this->venue,
            'status' => $this->status,
            'is_locked' => $this->isLocked(),
            'can_enter_marks' => $this->canEnterMarks(),
            'can_moderate' => $this->canModerate(),
            'can_approve' => $this->canApprove(),
            'class_subject' => new ClassSubjectResource($this->whenLoaded('classSubject')),
            'student_marks' => StudentMarkResource::collection($this->whenLoaded('studentMarks')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
