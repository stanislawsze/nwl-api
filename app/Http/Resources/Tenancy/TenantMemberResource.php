<?php

namespace App\Http\Resources\Tenancy;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantMemberResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $membershipRole = $this->resource->pivot?->getAttribute('role');

        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'email' => $this->resource->email,
            'membership_role' => is_string($membershipRole) ? $membershipRole : null,
            'permissions' => is_string($membershipRole)
                ? config('tenancy.membership_roles.' . $membershipRole, [])
                : [],
            'is_current_user' => $request->user()?->is($this->resource) ?? false,
            'joined_at' => $this->resource->pivot?->created_at?->toISOString(),
        ];
    }
}
