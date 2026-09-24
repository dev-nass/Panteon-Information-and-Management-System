<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClerkMiddleware;
use App\Http\Middleware\EnsureSingleSession;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureSingleSession::class,
        ]);

        $middleware->alias([
            'clerk' => ClerkMiddleware::class,
            'admin' => AdminMiddleware::class,
        ]);
        // Trust Railway's load balancer / reverse proxy
        $middleware->trustProxies(at: '*');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
