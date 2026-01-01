<?php

namespace App\Http\Resources\Notification;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'template_id' => $this->template_id,
            'type' => $this->type,
            'subject' => $this->subject,
            'body' => $this->body,
            'status' => $this->status,
            'sent_at' => $this->sent_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'read_at' => $this->read_at?->toISOString(),
            'failure_reason' => $this->failure_reason,
            'metadata' => $this->metadata,
            'template' => new NotificationTemplateResource($this->whenLoaded('template')),
            'created_by' => new UserResource($this->whenLoaded('createdByUser')),
            'recipients' => NotificationRecipientResource::collection($this->whenLoaded('recipients')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
