<?php

use App\Http\Middleware\AdminOnlyMiddleware;
use App\Http\Middleware\AppOnlyMiddleware;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__."/../routes/web.php",
        api: __DIR__."/../routes/api.php",
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Percaya proxy (cloudflared tunnel, dsb) agar skema http/https
        // terdeteksi dari header X-Forwarded-Proto — cegah mixed content.
        $middleware->trustProxies(at: '*');
        $middleware->alias(['api.key' => \App\Http\Middleware\ApiKeyMiddleware::class]);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);
        $middleware->redirectTo(function ($e) {
            return 'login';
        }, function ($e) {
            if ($e->user()->role == 'admin') {
                return 'admin';
            }

            return 'app';
        });

        $middleware->alias([
            'admin-only' => AdminOnlyMiddleware::class,
            'app-only' => AppOnlyMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
