<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GradeScaleResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'grading_system_id' => $this->grading_system_id,
            'grade' => $this->grade,
            'grade_label' => $this->grade_label,
            'min_score' => (float) $this->min_score,
            'max_score' => (float) $this->max_score,
            'gpa_points' => $this->gpa_points ? (float) $this->gpa_points : null,
            'points' => $this->points,
            'remark' => $this->remark,
            'is_passing' => $this->is_passing,
            'sort_order' => $this->sort_order,
        ];
    }
}
