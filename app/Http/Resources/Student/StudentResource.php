<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class StudentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'admission_number' => $this->admission_number,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'date_of_birth' => $this->date_of_birth?->toDateString(),
            'gender' => $this->gender,
            'nationality' => $this->nationality,
            'religion' => $this->religion,
            'blood_group' => $this->blood_group,
            'medical_conditions' => $this->medical_conditions,
            'address' => $this->address,
            'previous_school' => $this->previous_school,
            'admission_date' => $this->admission_date?->toDateString(),
            'status' => $this->status,
            'photo_url' => $this->getFirstMediaUrl('photo'),
            'guardians' => GuardianResource::collection($this->whenLoaded('guardians')),
            'current_enrollment' => new StudentEnrollmentResource($this->whenLoaded('currentEnrollment')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
