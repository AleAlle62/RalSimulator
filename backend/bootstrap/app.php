<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Same-origin session cookie auth, per CLAUDE.md: the SPA and the API share one
        // origin, so Sanctum authenticates by session rather than by bearer token.
        $middleware->statefulApi();

        // Every API route gets a ceiling. Laravel only adds `throttle:api` to the group when a
        // limiter is named here, so without this line there is no limit anywhere: POST /api/login
        // would take password guesses as fast as they arrive. The limiters themselves live in
        // AppServiceProvider, and the two endpoints that hand out a session name a tighter one
        // of their own in routes/api.php.
        $middleware->throttleApi();

        // There is no server rendered login page to send a guest to — login is a JSON
        // endpoint the SPA calls, and routing an unauthenticated visitor is the client's
        // job. Left at Laravel's default, a guest request without an explicit
        // "Accept: application/json" header would crash on route('login') instead of
        // getting a clean 401, since this app never defines that named route.
        $middleware->redirectGuestsTo(fn () => null);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
