<?php

namespace App\\Http\\Middleware;

use Closure;
use Illuminate\\Http\\Request;
use Symfony\\Component\\HttpFoundation\\Response;

class AuthenticateImageApiKey
{
    public function handle(Request $request, Closure $next): Response
    {
        $configuredKey = (string) config('api_gateway.key');
        $providedKey = (string) $request->bearerToken();

        if ($configuredKey === '' || $providedKey === '' || ! hash_equals($configuredKey, $providedKey)) {
            return response()->json([
                'error' => [
                    'message' => 'Invalid or missing API key.',
                    'type' => 'authentication_error',
                ],
            ], 401);
        }

        return $next($request);
    }
}
