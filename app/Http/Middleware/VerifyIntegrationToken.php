<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VerifyIntegrationToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $configured = (string) config('integration.token');
        $provided = (string) $request->header('X-Integration-Token', '');

        if ($configured === '' || $provided === '' || !hash_equals($configured, $provided)) {
            return response()->json(['message' => 'Integration request was not authorized.'], 401);
        }

        return $next($request);
    }
}
