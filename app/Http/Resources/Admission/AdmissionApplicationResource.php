<?php

namespace App\Http\Resources\Admission;

use App\Http\Resources\SchoolClass\SchoolClassResource;
use App\Http\Resources\SchoolClass\StreamResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionApplicationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'admission_period_id' => $this->admission_period_id,
            'application_number' => $this->application_number,
            'class_id' => $this->class_id,
            'stream_id' => $this->stream_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => trim("{$this->first_name} {$this->middle_name} {$this->last_name}"),
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'address' => $this->address,
            'previous_school' => $this->previous_school,
            'previous_class' => $this->previous_class,
            'guardian_name' => $this->guardian_name,
            'guardian_relationship' => $this->guardian_relationship,
            'guardian_phone' => $this->guardian_phone,
            'guardian_email' => $this->guardian_email,
            'guardian_address' => $this->guardian_address,
            'guardian_occupation' => $this->guardian_occupation,
            'status' => $this->status,
            'interview_date' => $this->interview_date?->toISOString(),
            'interview_notes' => $this->interview_notes,
            'interview_score' => $this->interview_score,
            'rejection_reason' => $this->rejection_reason,
            'photo_url' => $this->getFirstMediaUrl('photo'),
            'is_submitted' => $this->isSubmitted(),
            'is_approved' => $this->isApproved(),
            'is_rejected' => $this->isRejected(),
            'admission_period' => new AdmissionPeriodResource($this->whenLoaded('admissionPeriod')),
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'stream' => new StreamResource($this->whenLoaded('stream')),
            'documents' => ApplicationDocumentResource::collection($this->whenLoaded('documents')),
            'status_history' => ApplicationStatusHistoryResource::collection($this->whenLoaded('statusHistory')),
            'comments' => ApplicationCommentResource::collection($this->whenLoaded('comments')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
