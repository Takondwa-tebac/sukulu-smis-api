<?php

namespace App\Http\Resources\Student;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GuardianResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'last_name' => $this->last_name,
            'full_name' => $this->full_name,
            'relationship_type' => $this->relationship_type,
            'phone' => $this->phone,
            'phone_secondary' => $this->phone_secondary,
            'email' => $this->email,
            'occupation' => $this->occupation,
            'employer' => $this->employer,
            'address' => $this->address,
            'national_id' => $this->national_id,
            'is_active' => $this->is_active,
            'photo_url' => $this->getFirstMediaUrl('photo'),
            'pivot' => $this->whenPivotLoaded('student_guardian', fn () => [
                'relationship' => $this->pivot->relationship,
                'is_primary' => $this->pivot->is_primary,
                'is_emergency_contact' => $this->pivot->is_emergency_contact,
            ]),
            'students' => StudentResource::collection($this->whenLoaded('students')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
