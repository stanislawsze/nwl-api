<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\Services\TenancyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreTenantMemberRequest;
use App\Http\Requests\Tenancy\UpdateTenantMemberRequest;
use App\Http\Resources\Tenancy\TenantMemberResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantMemberController extends Controller
{
    public function __construct(
        protected TenancyService $tenancyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('viewMembers', $tenant);

        return response()->json([
            'data' => TenantMemberResource::collection(
                $this->tenancyService->membersForTenant($tenant),
            ),
            'meta' => [],
        ]);
    }

    public function store(StoreTenantMemberRequest $request): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('addMember', $tenant);

        $member = $this->tenancyService->addMemberByEmail(
            $request->user(),
            $request->validated('email'),
            $request->validated('role'),
        );

        return response()->json([
            'data' => new TenantMemberResource($member),
            'meta' => [
                'message' => 'Tenant member added successfully.',
            ],
        ], 201);
    }

    public function update(UpdateTenantMemberRequest $request, User $user): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('updateMember', $tenant);

        $member = $this->tenancyService->updateMemberRole(
            $request->user(),
            $user,
            $request->validated('role'),
        );

        return response()->json([
            'data' => new TenantMemberResource($member),
            'meta' => [
                'message' => 'Tenant member role updated successfully.',
            ],
        ]);
    }

    public function destroy(Request $request, User $user): JsonResponse
    {
        $tenant = $request->user()->currentTenantOrFail();
        $this->authorize('removeMember', $tenant);

        $this->tenancyService->removeMember($request->user(), $user);

        return response()->json([
            'data' => null,
            'meta' => [
                'message' => 'Tenant member removed successfully.',
            ],
        ]);
    }
}
