<?php

namespace App\Http\Resources\Tenancy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantInvitationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'tenant_id' => $this->resource->tenant_id,
            'email' => $this->resource->email,
            'role' => $this->resource->role,
            'permissions' => config('tenancy.membership_roles.' . $this->resource->role, []),
            'token' => $this->resource->token,
            'is_pending' => $this->resource->isPending(),
            'accepted_at' => $this->resource->accepted_at?->toISOString(),
            'revoked_at' => $this->resource->revoked_at?->toISOString(),
            'expires_at' => $this->resource->expires_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
