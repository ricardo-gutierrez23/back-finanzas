<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleManager
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (! $request->user() || $request->user()->role !== $role) {
            return response()->json(['status' => 'error', 'message' => 'Acceso denegado. Se requiere nivel: ' . $role], 403);
        }

        return $next($request);
    }
}
