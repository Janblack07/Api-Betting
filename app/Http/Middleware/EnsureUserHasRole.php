<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (! $request->user()) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
                'errors' => null,
            ], 401);
        }

        if (! $request->user()->isActive()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta no se encuentra activa.',
                'errors' => [
                    'status' => $request->user()->status,
                ],
            ], 403);
        }

        foreach ($roles as $role) {
            if ($request->user()->hasRole($role)) {
                return $next($request);
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No tienes permisos para realizar esta acción.',
            'errors' => [
                'required_roles' => $roles,
            ],
        ], 403);
    }
}
