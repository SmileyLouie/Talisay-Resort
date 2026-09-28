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
        // Lets the browser widgets (chatbot, notifications) call /api with the web session.
        $middleware->statefulApi();

        $middleware->alias([
            'role'        => \App\Http\Middleware\CheckRole::class,
            'permission'  => \App\Http\Middleware\CheckPermission::class,
            'integration' => \App\Http\Middleware\VerifyIntegrationToken::class,
        ]);

        // Stripe is signed. The mobile integration routes are server-to-server
        // and are authenticated by the integration token, not a browser session.
        $middleware->validateCsrfTokens(except: [
            'api/stripe/webhook',
            'api/integration/*',
        ]);

        $middleware->redirectTo(
            guests: '/login',
            users: '/',      // '/' redirects by role: admin / staff / tourist
        );
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })
    ->withProviders([
        \App\Providers\BroadcastServiceProvider::class,
    ])
    ->create();
