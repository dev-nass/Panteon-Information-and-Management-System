<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ClerkMiddleware;
use App\Http\Middleware\EnsureSingleSession;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

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
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->is('api/*')) {
                $status = 500;
                if ($e instanceof ValidationException) {
                    $status = 422;
                } elseif ($e instanceof AuthenticationException) {
                    $status = 401;
                } elseif ($e instanceof HttpExceptionInterface) {
                    $status = $e->getStatusCode();
                } elseif (method_exists($e, 'getCode') && is_int($e->getCode()) && $e->getCode() >= 100 && $e->getCode() < 600) {
                    $status = $e->getCode();
                }

                $payload = ['message' => $e->getMessage()];

                if ($e instanceof ValidationException) {
                    $payload['message'] = 'The given data was invalid.';
                    $payload['errors'] = $e->errors();
                }

                return response()->json($payload, $status);
            }

            return null;
        });
    })->create();
