<?php

namespace App\Http\Resources\Users;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'school_id' => $this->school_id,
            'first_name' => $this->first_name,
            'middle_name' => $this->middle_name,
            'initials' => $this->initials,
            'last_name' => $this->last_name,
            'name' => trim(($this->first_name ?? '') . ' ' . ($this->last_name ?? '')) ?: $this->username,
            'username' => $this->username,
            'phone_number' => $this->phone_number,
            'phone' => $this->phone_number,
            'email' => $this->email,
            'cover_photo' => $this->cover_photo,
            'profile_photo' => $this->profile_photo,
            'avatar_url' => $this->profile_photo,
            'email_verified_at' => $this->email_verified_at,
            'is_active' => $this->status === 'active',
            'status' => $this->status,
            'last_login_at' => $this->last_login_at,
            'roles' => $this->whenLoaded('roles', fn() => $this->roles->pluck('name')),
            'school' => $this->whenLoaded('school', fn() => [
                'id' => $this->school->id,
                'name' => $this->school->name,
            ]),
            'permissions' => $this->whenLoaded('permissions', fn() => $this->permissions->pluck('name')),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * Get additional data that should be returned with the resource array.
     *
     * @return array<string, mixed>
     */
    public function with($request)
    {
        return [];
    }

    /**
     * Customize the response for a request that includes a "fields" query parameter.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
    public function withResponse($request, $response)
    {
        $response->setStatusCode(200);

        $response->header('Content-Type', 'application/json');

        $response->header('ETag', md5($response->getContent()));
    }
}
