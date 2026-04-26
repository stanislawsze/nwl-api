<?php

namespace App\Http\Resources\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AuthResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'current_tenant' => $this->whenLoaded('currentTenant', function () {
                if ($this->resource->currentTenant === null) {
                    return null;
                }

                return [
                    'id' => $this->resource->currentTenant->id,
                    'name' => $this->resource->currentTenant->name,
                    'slug' => $this->resource->currentTenant->slug,
                    'owner_user_id' => $this->resource->currentTenant->owner_user_id,
                    'membership_role' => $this->resource->currentTenantMembershipRole(),
                    'permissions' => config(
                        'tenancy.membership_roles.' . ($this->resource->currentTenantMembershipRole() ?? 'member'),
                        [],
                    ),
                ];
            }),
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
