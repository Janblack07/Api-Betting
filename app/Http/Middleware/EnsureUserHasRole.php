<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'errors' => null,
            ], 401);
        }

        if (! $request->user()->hasRole($role)) {
            return response()->json([
                'success' => false,
                'message' => 'No tienes permisos para realizar esta acción.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
