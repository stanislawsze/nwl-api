<?php

namespace App\Http\Resources\Domain\Auth;

use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray($request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'email_verified_at' => $this->resource->email_verified_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
            'roles' => $this->whenLoaded('roles', function () {
                return $this->resource->roles->pluck('name')->toArray();
            }),
            'permissions' => $this->whenLoaded('permissions', function () {
                return $this->resource->permissions->pluck('name')->toArray();
            }),
        ];
    }
}
