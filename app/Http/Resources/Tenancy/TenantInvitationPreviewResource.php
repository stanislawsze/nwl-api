<?php

namespace App\Http\Resources\Tenancy;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TenantInvitationPreviewResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $hasExistingAccount = User::query()
            ->where('email', $this->resource->email)
            ->exists();
        $status = $this->resource->status();

        return [
            'email' => $this->resource->email,
            'role' => $this->resource->role,
            'permissions' => config('tenancy.membership_roles.' . $this->resource->role, []),
            'status' => $status,
            'is_pending' => $this->resource->isPending(),
            'has_existing_account' => $hasExistingAccount,
            'recommended_action' => $this->recommendedAction($status, $hasExistingAccount),
            'tenant' => [
                'id' => $this->resource->tenant?->id,
                'name' => $this->resource->tenant?->name,
                'slug' => $this->resource->tenant?->slug,
            ],
            'links' => [
                'accept' => $this->resource->frontendAcceptUrl(),
                'register' => $this->resource->frontendRegisterUrl(),
                'login' => $this->resource->frontendLoginUrl(),
                'api' => [
                    'show' => route('api.v1.tenants.invitations.show', ['token' => $this->resource->token]),
                    'register' => route('api.v1.tenants.invitations.register', ['token' => $this->resource->token]),
                    'accept' => route('api.v1.tenants.invitations.accept', ['token' => $this->resource->token]),
                ],
            ],
            'expires_at' => $this->resource->expires_at?->toISOString(),
            'accepted_at' => $this->resource->accepted_at?->toISOString(),
            'revoked_at' => $this->resource->revoked_at?->toISOString(),
            'created_at' => $this->resource->created_at?->toISOString(),
        ];
    }

    protected function recommendedAction(string $status, bool $hasExistingAccount): ?string
    {
        if ($status !== 'pending') {
            return null;
        }

        return $hasExistingAccount ? 'login' : 'register';
    }
}
