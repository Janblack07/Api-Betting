<?php

namespace App\Modules\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountIsActive
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && ! $user->allowsApiAuthentication()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta no está disponible para usar la API.',
                'errors' => null,
            ], 403);
        }

        return $next($request);
    }
}
