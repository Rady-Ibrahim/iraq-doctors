<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',

    )
    ->withMiddleware(function (Middleware $middleware) {
        // Note: EnsureFrontendRequestsAreStateful removed — this is a pure token API,
        // not a SPA/cookie-based app. Sanctum token auth works without it.

        $middleware->alias([
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'admin'            => \App\Http\Middleware\AdminMiddleware::class,
            'doctor'           => \App\Http\Middleware\DoctorMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Always return JSON for API and dashboard routes
        $exceptions->render(function (Throwable $e, $request) {
            if ($request->expectsJson() || $request->is('api/*') || $request->is('*/dashboard/*') || $request->is('admin/*') || $request->is('doctor/*')) {
                $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => class_basename($e),
                        'message' => config('app.debug')
                            ? $e->getMessage()
                            : 'حدث خطأ في الخادم',
                    ],
                ], $statusCode);
            }

            return null;
        });
    })->create();
