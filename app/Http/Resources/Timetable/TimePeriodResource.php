<?php

namespace App\Http\Resources\Timetable;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TimePeriodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'name' => $this->name,
            'start_time' => $this->start_time?->format('H:i'),
            'end_time' => $this->end_time?->format('H:i'),
            'type' => $this->type,
            'duration_minutes' => $this->getDurationMinutes(),
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
