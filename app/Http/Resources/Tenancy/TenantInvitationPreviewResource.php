<?php

namespace App\Http\Resources\Tenancy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantInvitationPreviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'email' => $this->resource->email,
            'role' => $this->resource->role,
            'permissions' => config('tenancy.membership_roles.' . $this->resource->role, []),
            'status' => $this->resource->status(),
            'is_pending' => $this->resource->isPending(),
            'tenant' => [
                'id' => $this->resource->tenant?->id,
                'name' => $this->resource->tenant?->name,
                'slug' => $this->resource->tenant?->slug,
            ],
            'expires_at' => $this->resource->expires_at?->toISOString(),
            'accepted_at' => $this->resource->accepted_at?->toISOString(),
            'revoked_at' => $this->resource->revoked_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }
}
