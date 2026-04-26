<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Discord\Services\DiscordBotService;
use App\Domain\Discord\Services\DiscordIntegrationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discord\ClearDiscordCredentialsRequest;
use App\Http\Requests\Discord\StoreDiscordRoleMappingRequest;
use App\Http\Requests\Discord\UpdateDiscordCredentialsRequest;
use App\Http\Requests\Discord\UpsertDiscordIntegrationRequest;
use App\Http\Resources\Discord\DiscordAccountResource;
use App\Http\Resources\Discord\DiscordGuildRoleResource;
use App\Http\Resources\Discord\DiscordIntegrationResource;
use App\Http\Resources\Discord\DiscordRoleMappingResource;
use App\Models\DiscordRoleMapping;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscordIntegrationController extends Controller
{
    public function __construct(
        protected DiscordIntegrationService $discordIntegrationService,
        protected DiscordBotService $discordBotService,
    ) {}

    public function show(Request $request): JsonResponse
    {
        $integration = $this->discordIntegrationService->currentTenantIntegrationWithMappings($request->user());

        return response()->json([
            'data' => $integration ? new DiscordIntegrationResource($integration) : null,
            'meta' => [
                'discord_account' => $request->user()->discordAccount ? new DiscordAccountResource($request->user()->discordAccount) : null,
            ],
        ]);
    }

    public function upsert(UpsertDiscordIntegrationRequest $request): JsonResponse
    {
        $integration = $this->discordIntegrationService->upsertIntegration($request->user(), $request->validated());

        return response()->json([
            'data' => new DiscordIntegrationResource($integration->load('roleMappings.localRole')),
            'meta' => [],
        ]);
    }

    public function updateCredentials(UpdateDiscordCredentialsRequest $request): JsonResponse
    {
        $integration = $this->discordIntegrationService->updateCredentials($request->user(), $request->validated());

        return response()->json([
            'data' => new DiscordIntegrationResource($integration->load('roleMappings.localRole')),
            'meta' => [
                'message' => 'Discord credentials updated.',
            ],
        ]);
    }

    public function clearCredentials(ClearDiscordCredentialsRequest $request): JsonResponse
    {
        $integration = $this->discordIntegrationService->clearCredentials(
            $request->user(),
            $request->validated('fields'),
        );

        return response()->json([
            'data' => new DiscordIntegrationResource($integration->load('roleMappings.localRole')),
            'meta' => [
                'message' => 'Discord credentials cleared.',
            ],
        ]);
    }

    public function listGuildRoles(Request $request): JsonResponse
    {
        $integration = $this->discordIntegrationService->currentTenantIntegration($request->user());

        if ($integration === null) {
            return response()->json([
                'message' => 'Discord integration not configured.',
                'code' => 'discord_integration_missing',
                'errors' => [],
            ], 422);
        }

        $roles = $this->discordBotService->fetchGuildRoles($integration);

        return response()->json([
            'data' => DiscordGuildRoleResource::collection(collect($roles)),
            'meta' => [],
        ]);
    }

    public function storeRoleMapping(StoreDiscordRoleMappingRequest $request): JsonResponse
    {
        $mapping = $this->discordIntegrationService->createRoleMapping($request->user(), $request->validated());

        return response()->json([
            'data' => new DiscordRoleMappingResource($mapping),
            'meta' => [],
        ], 201);
    }

    public function destroyRoleMapping(Request $request, DiscordRoleMapping $mapping): JsonResponse
    {
        $this->discordIntegrationService->deleteRoleMapping($request->user(), $mapping);

        return response()->json([
            'data' => null,
            'meta' => [
                'message' => 'Discord role mapping deleted.',
            ],
        ]);
    }
}
