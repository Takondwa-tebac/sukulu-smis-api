<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradingSystemResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'type' => $this->type,
            'scale_type' => $this->scale_type,
            'min_score' => (float) $this->min_score,
            'max_score' => (float) $this->max_score,
            'pass_mark' => (float) $this->pass_mark,
            'min_subjects_to_pass' => $this->min_subjects_to_pass,
            'priority_subjects' => $this->priority_subjects,
            'certification_rules' => $this->certification_rules,
            'progression_rules' => $this->progression_rules,
            'settings' => $this->settings,
            'version' => $this->version,
            'is_system_default' => $this->is_system_default,
            'is_locked' => $this->is_locked,
            'is_active' => $this->is_active,
            'grade_scales' => GradeScaleResource::collection($this->whenLoaded('gradeScales')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
