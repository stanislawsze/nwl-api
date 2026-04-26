<?php

namespace App\Domain\Tenancy\Services;

use App\Models\Tenant;
use App\Models\TenantInvitation;
use App\Models\User;

class TenancyAuditLogService
{
    public function tenantCreated(User $actor, Tenant $tenant): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties([
                'tenant_id' => $tenant->id,
                'tenant_name' => $tenant->name,
                'tenant_slug' => $tenant->slug,
            ])
            ->event('tenant.created')
            ->log('Tenant created');
    }

    public function tenantSwitched(User $actor, Tenant $tenant, ?int $previousTenantId): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties([
                'tenant_id' => $tenant->id,
                'previous_tenant_id' => $previousTenantId,
                'current_tenant_id' => $tenant->id,
            ])
            ->event('tenant.switched')
            ->log('Tenant switched');
    }

    public function memberAdded(User $actor, Tenant $tenant, User $member, string $role): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties([
                'tenant_id' => $tenant->id,
                'member_id' => $member->id,
                'member_email' => $member->email,
                'role' => $role,
            ])
            ->event('tenant.member_added')
            ->log('Tenant member added');
    }

    public function memberRoleUpdated(User $actor, Tenant $tenant, User $member, ?string $previousRole, string $newRole): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties([
                'tenant_id' => $tenant->id,
                'member_id' => $member->id,
                'member_email' => $member->email,
                'previous_role' => $previousRole,
                'new_role' => $newRole,
            ])
            ->event('tenant.member_role_updated')
            ->log('Tenant member role updated');
    }

    public function memberRemoved(User $actor, Tenant $tenant, User $member, ?int $nextTenantId): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($tenant)
            ->withProperties([
                'tenant_id' => $tenant->id,
                'member_id' => $member->id,
                'member_email' => $member->email,
                'next_tenant_id' => $nextTenantId,
            ])
            ->event('tenant.member_removed')
            ->log('Tenant member removed');
    }

    public function invitationCreated(User $actor, TenantInvitation $invitation): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($invitation->tenant)
            ->withProperties($this->invitationProperties($invitation))
            ->event('tenant.invitation_created')
            ->log('Tenant invitation created');
    }

    public function invitationResent(User $actor, TenantInvitation $invitation): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($invitation->tenant)
            ->withProperties($this->invitationProperties($invitation))
            ->event('tenant.invitation_resent')
            ->log('Tenant invitation resent');
    }

    public function invitationRevoked(User $actor, TenantInvitation $invitation): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($invitation->tenant)
            ->withProperties($this->invitationProperties($invitation))
            ->event('tenant.invitation_revoked')
            ->log('Tenant invitation revoked');
    }

    public function invitationAccepted(User $actor, TenantInvitation $invitation): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($invitation->tenant)
            ->withProperties($this->invitationProperties($invitation) + [
                'accepted_by_user_id' => $invitation->accepted_by_user_id,
            ])
            ->event('tenant.invitation_accepted')
            ->log('Tenant invitation accepted');
    }

    public function invitationRegistered(User $actor, TenantInvitation $invitation): void
    {
        activity('tenancy')
            ->causedBy($actor)
            ->performedOn($invitation->tenant)
            ->withProperties($this->invitationProperties($invitation) + [
                'registered_user_id' => $actor->id,
            ])
            ->event('tenant.invitation_registered')
            ->log('Tenant invitation registration completed');
    }

    /**
     * @return array<string, mixed>
     */
    protected function invitationProperties(TenantInvitation $invitation): array
    {
        return [
            'tenant_id' => $invitation->tenant_id,
            'tenant_invitation_id' => $invitation->id,
            'email' => $invitation->email,
            'role' => $invitation->role,
            'status' => $invitation->status(),
        ];
    }
}
