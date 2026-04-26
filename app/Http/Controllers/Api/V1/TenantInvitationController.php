<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\DTOs\RegisterUserDTO;
use App\Domain\Auth\Services\AuthService;
use App\Domain\Tenancy\Services\TenancyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\RegisterFromInvitationRequest;
use App\Http\Requests\Tenancy\StoreTenantInvitationRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Http\Resources\Tenancy\TenantInvitationPreviewResource;
use App\Http\Resources\Tenancy\TenantInvitationResource;
use App\Models\TenantInvitation;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantInvitationController extends Controller
{
    public function __construct(
        protected TenancyService $tenancyService,
        protected AuthService $authService,
    ) {}

    public function show(string $token): JsonResponse
    {
        $invitation = $this->tenancyService->invitationByTokenOrFail($token);

        return response()->json([
            'data' => new TenantInvitationPreviewResource($invitation),
            'meta' => [],
        ]);
    }

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

    public function register(RegisterFromInvitationRequest $request, string $token): JsonResponse
    {
        $invitation = $this->tenancyService->invitationByTokenOrFail($token);
        $authenticatedUser = $this->authService->registerFromInvitation(
            new RegisterUserDTO(
                name: $request->validated('name'),
                email: $invitation->email,
                password: $request->validated('password'),
            ),
            $token,
        );

        return $this->authenticatedResponse($authenticatedUser->userId, $authenticatedUser->token, 201);
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

    protected function authenticatedResponse(int $userId, string $token, int $status = 200): JsonResponse
    {
        $user = User::query()
            ->with(['roles', 'permissions', 'currentTenant'])
            ->findOrFail($userId);
        $user->currentTenantOrFail();

        return response()->json([
            'data' => [
                'user' => new AuthResource($user),
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ], $status);
    }
}
