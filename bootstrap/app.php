<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Trust the local TLS proxy (used for HTTPS phone testing) so
        // X-Forwarded-Proto/Host are honored and URLs render as https://.
        $middleware->trustProxies(at: ['127.0.0.1', '::1']);

        // The `theme` cookie is written by client-side JS (unencrypted) and read
        // server-side to render the dark class before paint; exclude it from
        // cookie encryption so Laravel can read its raw value.
        $middleware->encryptCookies(except: ['theme']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
