<?php

namespace App\Http\Resources\Subject;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SubjectResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'name' => $this->name,
            'code' => $this->code,
            'category' => $this->category,
            'department_id' => $this->department_id,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'is_elective' => $this->is_elective,
            'credit_hours' => $this->credit_hours,
            'sort_order' => $this->sort_order,
            'department' => new DepartmentResource($this->whenLoaded('department')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
