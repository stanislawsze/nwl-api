<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->hasPermissionTo($permission)) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'authorization_denied',
                'errors' => [],
            ], 403);
        }

        return $next($request);
    }
}
