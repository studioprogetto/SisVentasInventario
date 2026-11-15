<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $scriptSources = $request->routeIs('login') ? "script-src 'self' https://cdnjs.cloudflare.com" : "script-src 'self'";
        $response->headers->set('Content-Security-Policy', "default-src 'self'; img-src 'self' data: https: http:; style-src 'self' 'unsafe-inline'; {$scriptSources}; object-src 'none'; base-uri 'self'; form-action 'self'");
        if ($request->routeIs('tickets.*')) {
            $response->headers->set('Referrer-Policy', 'no-referrer');
            $response->headers->set('X-Robots-Tag', 'noindex, nofollow');
        }
        if ($request->user() || $request->routeIs('tickets.*')) {
            $response->headers->set('Cache-Control', 'no-store,private');
        }

        return $response;
    }
}
