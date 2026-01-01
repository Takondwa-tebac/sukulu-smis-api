<?php

namespace App\Http\Resources\Admission;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ApplicationStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'admission_application_id' => $this->admission_application_id,
            'from_status' => $this->from_status,
            'to_status' => $this->to_status,
            'notes' => $this->notes,
            'changed_by' => new UserResource($this->whenLoaded('changedByUser')),
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
