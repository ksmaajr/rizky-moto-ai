<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ShowTakeover
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('takeover.enabled') && $request->isMethodSafe() && ($request->is('/') || $request->is('login'))) {
            return response()->view('takeover', [], 200, ['Cache-Control' => 'no-store, private']);
        }

        return $next($request);
    }
}
