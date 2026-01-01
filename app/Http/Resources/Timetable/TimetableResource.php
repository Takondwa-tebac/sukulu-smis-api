<?php

namespace App\Http\Resources\Timetable;

use App\Http\Resources\AcademicYear\AcademicYearResource;
use App\Http\Resources\AcademicYear\TermResource;
use App\Http\Resources\SchoolClass\SchoolClassResource;
use App\Http\Resources\SchoolClass\StreamResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimetableResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'academic_year_id' => $this->academic_year_id,
            'term_id' => $this->term_id,
            'class_id' => $this->class_id,
            'stream_id' => $this->stream_id,
            'name' => $this->name,
            'effective_from' => $this->effective_from?->toDateString(),
            'effective_to' => $this->effective_to?->toDateString(),
            'status' => $this->status,
            'academic_year' => new AcademicYearResource($this->whenLoaded('academicYear')),
            'term' => new TermResource($this->whenLoaded('term')),
            'school_class' => new SchoolClassResource($this->whenLoaded('schoolClass')),
            'stream' => new StreamResource($this->whenLoaded('stream')),
            'slots' => TimetableSlotResource::collection($this->whenLoaded('slots')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
