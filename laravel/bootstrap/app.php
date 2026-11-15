<?php

use App\Http\Middleware\EnsureActiveUser;
use App\Http\Middleware\EnsureWritableEnvironment;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);
        $middleware->alias([
            'active' => EnsureActiveUser::class,
            'writable' => EnsureWritableEnvironment::class,
        ]);
        $middleware->redirectGuestsTo(fn () => route('login'));
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (Throwable $exception) {
            if ($exception instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface && $exception->getStatusCode() < 500) return;
            try {
                if (!app()->environment('testing')) \App\Services\Notifications\IncidentCause::record($exception);
            } catch (Throwable) {
                // Logging the original exception still proceeds if storage is unavailable.
            }
        });
    })->create();
