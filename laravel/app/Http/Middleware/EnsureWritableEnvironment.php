<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureWritableEnvironment
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(config('migration.read_only'), 423, 'La nueva aplicación está en modo de verificación.');

        return $next($request);
    }
}
