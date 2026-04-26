<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Tenancy\Services\TenancyService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenancy\StoreTenantRequest;
use App\Http\Resources\Tenancy\TenantResource;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TenantController extends Controller
{
    public function __construct(
        protected TenancyService $tenancyService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorize('viewAny', Tenant::class);

        $tenants = $this->tenancyService->tenantsForUser($request->user());

        return response()->json([
            'data' => TenantResource::collection($tenants),
            'meta' => [],
        ]);
    }

    public function store(StoreTenantRequest $request): JsonResponse
    {
        $this->authorize('create', Tenant::class);

        $tenant = $this->tenancyService->createTenant($request->user(), $request->validated('name'));
        $tenant = $request->user()
            ->tenants()
            ->with('owner')
            ->findOrFail($tenant->id);

        return response()->json([
            'data' => new TenantResource($tenant),
            'meta' => [
                'message' => 'Tenant created successfully.',
            ],
        ], 201);
    }

    public function switchCurrent(Request $request, Tenant $tenant): JsonResponse
    {
        $this->authorize('switch', $tenant);

        $tenant = $this->tenancyService->switchTenant($request->user(), $tenant);
        $tenant = $request->user()
            ->tenants()
            ->with('owner')
            ->findOrFail($tenant->id);

        return response()->json([
            'data' => new TenantResource($tenant),
            'meta' => [
                'message' => 'Tenant switched successfully.',
            ],
        ]);
    }
}
