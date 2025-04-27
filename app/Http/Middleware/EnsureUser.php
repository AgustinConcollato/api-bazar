<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUser
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!($request->user() instanceof \App\Models\User)) {
            return response()->json(['error' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}
