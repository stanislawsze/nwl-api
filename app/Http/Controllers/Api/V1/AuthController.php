<?php

namespace App\Http\Controllers\Api\V1;

use App\Domain\Auth\DTOs\LoginUserDTO;
use App\Domain\Auth\DTOs\RegisterUserDTO;
use App\Domain\Auth\Services\AuthService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\Auth\AuthResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $authenticatedUser = $this->authService->register(new RegisterUserDTO(...$request->validated()));

        return $this->authenticatedResponse($authenticatedUser->userId, $authenticatedUser->token, 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $authenticatedUser = $this->authService->authenticate(new LoginUserDTO(...$request->validated()));

        return $this->authenticatedResponse($authenticatedUser->userId, $authenticatedUser->token);
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return $this->emptyResponse('Successfully logged out.');
    }

    public function me(Request $request): JsonResponse
    {
        $request->user()?->currentTenantOrFail();

        return response()->json([
            'data' => new AuthResource($request->user()?->load('roles', 'permissions', 'currentTenant')),
            'meta' => [],
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $currentToken = $request->user()?->currentAccessToken();

        if ($currentToken === null) {
            abort(401);
        }

        $currentToken->delete();

        return $this->tokenResponse($request->user()->createToken('auth-token')->plainTextToken);
    }

    protected function authenticatedResponse(int $userId, string $token, int $status = 200): JsonResponse
    {
        $user = User::query()
            ->with(['roles', 'permissions', 'currentTenant'])
            ->findOrFail($userId);
        $user->currentTenantOrFail();
        $user->load('currentTenant');

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

    protected function tokenResponse(string $token): JsonResponse
    {
        return response()->json([
            'data' => [
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ]);
    }

    protected function emptyResponse(string $message): JsonResponse
    {
        return response()->json([
            'data' => null,
            'meta' => [
                'message' => $message,
            ],
        ]);
    }
}
