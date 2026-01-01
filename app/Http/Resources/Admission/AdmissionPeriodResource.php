<?php

namespace App\Http\Resources\Admission;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdmissionPeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'academic_year_id' => $this->academic_year_id,
            'name' => $this->name,
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'status' => $this->status,
            'application_fee' => (float) $this->application_fee,
            'max_applications' => $this->max_applications,
            'requirements' => $this->requirements,
            'settings' => $this->settings,
            'is_open' => $this->isOpen(),
            'is_accepting_applications' => $this->isAcceptingApplications(),
            'applications_count' => $this->whenCounted('applications'),
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
