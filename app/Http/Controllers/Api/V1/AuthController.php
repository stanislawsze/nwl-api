<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Domain\Auth\LoginRequest;
use App\Http\Requests\Domain\Auth\RegisterRequest;
use App\Http\Resources\Domain\Auth\AuthResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Register a new user.
     *
     * @return JsonResponse
     */
    public function register(RegisterRequest $request)
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        // Assign default 'user' role
        $user->assignRole('user');

        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new AuthResource($user->load('roles', 'permissions')),
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ], 201);
    }

    /**
     * Login user and issue token.
     *
     * @return JsonResponse
     */
    public function login(LoginRequest $request)
    {
        $validated = $request->validated();

        if (! Auth::attempt(['email' => $validated['email'], 'password' => $validated['password']])) {
            throw ValidationException::withMessages([
                'email' => ['The provided credentials are incorrect.'],
            ]);
        }

        $user = Auth::user();
        $token = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'user' => new AuthResource($user->load('roles', 'permissions')),
                'token' => $token,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ]);
    }

    /**
     * Logout user and revoke token.
     *
     * @return JsonResponse
     */
    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json([
            'data' => null,
            'meta' => [
                'message' => 'Successfully logged out.',
            ],
        ]);
    }

    /**
     * Get authenticated user info.
     *
     * @return JsonResponse
     */
    public function me(Request $request)
    {
        return response()->json([
            'data' => new AuthResource($request->user()->load('roles', 'permissions')),
        ]);
    }

    /**
     * Refresh token (issue new token, revoke old one).
     *
     * @return JsonResponse
     */
    public function refresh(Request $request)
    {
        $currentToken = $request->user()?->currentAccessToken();

        if (! $currentToken) {
            return response()->json([
                'message' => 'Unauthorized',
                'code' => 'no_active_token',
                'errors' => ['No active token found.'],
            ], 401);
        }

        $user = $request->user();
        $currentToken->delete();

        $newToken = $user->createToken('auth-token')->plainTextToken;

        return response()->json([
            'data' => [
                'token' => $newToken,
            ],
            'meta' => [
                'token_type' => 'Bearer',
            ],
        ]);
    }
}
