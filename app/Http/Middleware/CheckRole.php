<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = Auth::user();

        if (! $user || ! $user->hasRole($role)) {
            return response()->json([
                'message' => 'Forbidden',
                'code' => 'insufficient_permissions',
                'errors' => ['You do not have the required role.'],
            ], 403);
        }

        return $next($request);
    }
}
