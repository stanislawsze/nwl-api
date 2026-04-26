<?php

namespace App\Http\Resources\Tenancy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $currentTenantId = $request->user()?->current_tenant_id;
        $membershipRole = $this->resource->pivot?->role;

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'slug' => $this->resource->slug,
            'owner_user_id' => $this->resource->owner_user_id,
            'membership_role' => $membershipRole,
            'permissions' => is_string($membershipRole)
                ? config('tenancy.membership_roles.' . $membershipRole, [])
                : [],
            'is_current' => $currentTenantId !== null && $currentTenantId === $this->resource->id,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
