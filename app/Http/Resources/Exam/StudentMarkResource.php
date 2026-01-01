<?php

namespace App\Http\Resources\Exam;

use App\Http\Resources\Student\StudentResource;
use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentMarkResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'exam_subject_id' => $this->exam_subject_id,
            'student_id' => $this->student_id,
            'score' => $this->score ? (float) $this->score : null,
            'grade' => $this->grade,
            'remarks' => $this->remarks,
            'is_absent' => $this->is_absent,
            'absent_reason' => $this->absent_reason,
            'status' => $this->status,
            'is_locked' => $this->isLocked(),
            'original_score' => $this->original_score ? (float) $this->original_score : null,
            'moderation_reason' => $this->moderation_reason,
            'entered_at' => $this->entered_at?->toISOString(),
            'moderated_at' => $this->moderated_at?->toISOString(),
            'approved_at' => $this->approved_at?->toISOString(),
            'student' => new StudentResource($this->whenLoaded('student')),
            'entered_by' => new UserResource($this->whenLoaded('enteredByUser')),
            'moderated_by' => new UserResource($this->whenLoaded('moderatedByUser')),
            'approved_by' => new UserResource($this->whenLoaded('approvedByUser')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
