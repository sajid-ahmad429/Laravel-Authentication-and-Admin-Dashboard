<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        /*
        |----------------------------------------------------------------------
        | Global / Web Middleware Hardening
        |----------------------------------------------------------------------
        */
        $middleware->web(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        /*
        |----------------------------------------------------------------------
        | Route Middleware Aliases
        |----------------------------------------------------------------------
        |
        | auth.session : Verifies the custom session-based login flag.
        | role.access  : Fail-closed role / panel authorization gate.
        |
        */
        $middleware->alias([
            'auth.session' => \App\Http\Middleware\EnsureSessionAuthenticated::class,
            'role.access'  => \App\Http\Middleware\RoleMiddleware::class,
        ]);

        /*
        |----------------------------------------------------------------------
        | Validation / Redirect Defaults
        |----------------------------------------------------------------------
        */
        $middleware->redirectGuestsTo(fn () => route('login'));
        $middleware->validateCsrfTokens(except: []);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->shouldRenderJsonWhen(fn ($request, $e) => $request->expectsJson() || $request->ajax());
    })->create();
