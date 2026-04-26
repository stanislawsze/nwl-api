<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Discord\Services\DiscordAuthenticationService;
use App\Domain\Discord\Services\DiscordIntegrationService;
use App\Domain\Discord\Services\DiscordOAuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Discord\DiscordCallbackRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Models\DiscordIntegration;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DiscordAuthController extends Controller
{
    public function __construct(
        protected DiscordOAuthService $discordOAuthService,
        protected DiscordAuthenticationService $discordAuthenticationService,
        protected DiscordIntegrationService $discordIntegrationService,
    ) {}

    public function redirect(Request $request): JsonResponse
    {
        $integration = null;

        if ($request->filled('integration_id')) {
            $integration = DiscordIntegration::query()->findOrFail((int) $request->integer('integration_id'));
        }

        $redirect = $this->discordOAuthService->buildAuthorizationRedirect($integration);

        return response()->json([
            'data' => [
                'authorization_url' => $redirect->authorizationUrl,
                'state' => $redirect->state,
            ],
            'meta' => [],
        ]);
    }

    public function callback(DiscordCallbackRequest $request): JsonResponse
    {
        $payload = $this->discordOAuthService->fetchUserFromAuthorizationCode(
            $request->string('code')->toString(),
            $request->string('state')->toString(),
        );

        $integration = null;

        if (is_int($payload['integration_id'])) {
            $integration = DiscordIntegration::query()->find($payload['integration_id']);
        }

        $authenticatedUser = $this->discordAuthenticationService->authenticate(
            $payload['user'],
            $integration,
        );

        return $this->authenticatedResponse($authenticatedUser->userId, $authenticatedUser->token);
    }

    protected function authenticatedResponse(int $userId, string $token): JsonResponse
    {
        $user = User::query()
            ->with(['roles', 'permissions', 'discordAccount'])
            ->findOrFail($userId);

        return response()->json([
            'data' => [
                'user' => new AuthResource($user),
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
