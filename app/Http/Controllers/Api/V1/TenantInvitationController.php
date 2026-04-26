<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\Services\TenancyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreTenantInvitationRequest;
use App\Http\Resources\Tenancy\TenantInvitationResource;
use App\Models\TenantInvitation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantInvitationController extends Controller
{
    public function __construct(
        protected TenancyService $tenancyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('viewInvitations', $tenant);

        return response()->json([
            'data' => TenantInvitationResource::collection(
                $this->tenancyService->invitationsForTenant($tenant),
            ),
            'meta' => [],
        ]);
    }

    public function store(StoreTenantInvitationRequest $request): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('inviteMember', $tenant);

        $invitation = $this->tenancyService->createInvitation(
            $request->user(),
            $request->validated('email'),
            $request->validated('role'),
            $request->validated('expires_in_hours'),
        );

        return response()->json([
            'data' => new TenantInvitationResource($invitation),
            'meta' => [
                'message' => 'Tenant invitation created successfully.',
            ],
        ], 201);
    }

    public function resend(Request $request, TenantInvitation $tenantInvitation): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('resendInvitation', $tenant);

        $invitation = $this->tenancyService->resendInvitation($request->user(), $tenantInvitation);

        return response()->json([
            'data' => new TenantInvitationResource($invitation),
            'meta' => [
                'message' => 'Tenant invitation resent successfully.',
            ],
        ]);
    }

    public function accept(Request $request, string $token): JsonResponse
    {
        $invitation = $this->tenancyService->acceptInvitation($request->user(), $token);

        return response()->json([
            'data' => new TenantInvitationResource($invitation),
            'meta' => [
                'message' => 'Tenant invitation accepted successfully.',
            ],
        ]);
    }

    public function destroy(Request $request, TenantInvitation $tenantInvitation): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('revokeInvitation', $tenant);

        $this->tenancyService->revokeInvitation($request->user(), $tenantInvitation);

        return response()->json([
            'data' => null,
            'meta' => [
                'message' => 'Tenant invitation revoked successfully.',
            ],
        ]);
    }
}
