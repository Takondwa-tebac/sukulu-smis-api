<?php

namespace App\Http\Resources\Admin;

use App\Http\Resources\User\UserResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SchoolResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'type' => $this->type,
            'address' => $this->address,
            'city' => $this->city,
            'region' => $this->region,
            'country' => $this->country,
            'postal_code' => $this->postal_code,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'motto' => $this->motto,
            'established_year' => $this->established_year,
            'registration_number' => $this->registration_number,
            'status' => $this->status,
            'subscription_plan' => $this->subscription_plan,
            'subscription_expires_at' => $this->subscription_expires_at?->toISOString(),
            'enabled_modules' => $this->enabled_modules,
            'logo_url' => $this->getFirstMediaUrl('logo'),
            'users_count' => $this->whenCounted('users'),
            'admin_user' => new UserResource($this->whenLoaded('adminUser')),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
        ];
    }
}
