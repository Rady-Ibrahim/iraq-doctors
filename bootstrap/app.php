<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Session\TokenMismatchException;
use Illuminate\Validation\ValidationException;
use Throwable;

function expectsStructuredJson(Request $request): bool
{
    return $request->expectsJson()
        || $request->is('api/*')
        || $request->is('admin/api/*')
        || $request->is('doctor/api/*')
        || $request->is('laboratory/api/*')
        || $request->is('pharmacy/api/*');
}

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withSchedule(function (Schedule $schedule) {
        $schedule->command('subscriptions:process')->dailyAt('08:00');
    })
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('doctor/*')) {
                return route('doctor.login');
            }

            if ($request->is('admin/*')) {
                return route('admin.login');
            }

            if ($request->is('laboratory/*')) {
                return route('laboratory.login');
            }

            if ($request->is('pharmacy/*')) {
                return route('pharmacy.login');
            }

            return '/doctor/login';
        });

        $middleware->alias([
            'admin'            => \App\Http\Middleware\AdminMiddleware::class,
            'doctor'           => \App\Http\Middleware\DoctorMiddleware::class,
            'doctor.approved'  => \App\Http\Middleware\DoctorApprovedMiddleware::class,
            'doctor.email.verified' => \App\Http\Middleware\DoctorEmailVerifiedMiddleware::class,
            'doctor.phone.verified' => \App\Http\Middleware\DoctorPhoneVerifiedMiddleware::class,
            'laboratory'           => \App\Http\Middleware\LaboratoryMiddleware::class,
            'laboratory.approved'  => \App\Http\Middleware\LaboratoryApprovedMiddleware::class,
            'laboratory.phone.verified' => \App\Http\Middleware\LaboratoryPhoneVerifiedMiddleware::class,
            'pharmacy'             => \App\Http\Middleware\PharmacyMiddleware::class,
            'pharmacy.approved'    => \App\Http\Middleware\PharmacyApprovedMiddleware::class,
            'pharmacy.phone.verified' => \App\Http\Middleware\PharmacyPhoneVerifiedMiddleware::class,
            'session.scope'       => \App\Http\Middleware\SetSessionCookie::class,
            'role'             => \App\Http\Middleware\RoleMiddleware::class,
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);

        $middleware->priority([
            \App\Http\Middleware\SetSessionCookie::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {

        $exceptions->render(function (Throwable $e, Request $request) {

            if ($e instanceof TokenMismatchException) {
                if ($request->expectsJson() || $request->is('*/api/*') || $request->ajax()) {
                    return response()->json(['success' => false], 419);
                }

                if ($request->is('admin/*')) {
                    return redirect()->route('admin.login');
                }

                if ($request->is('doctor/*')) {
                    return redirect()->route('doctor.login');
                }

                if ($request->is('laboratory/*')) {
                    return redirect()->route('laboratory.login');
                }

                if ($request->is('pharmacy/*')) {
                    return redirect()->route('pharmacy.login');
                }

                return redirect('/');
            }

            if ($e instanceof AuthenticationException) {
                if ($request->expectsJson() || $request->is('*/api/*') || $request->ajax()) {
                    return response()->json(['success' => false], 401);
                }
            }

            if ($e instanceof ValidationException && expectsStructuredJson($request)) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'VALIDATION_ERROR',
                        'message' => 'يرجى تصحيح الحقول المحددة',
                        'details' => $e->errors(),
                    ],
                ], 422);
            }

            if (expectsStructuredJson($request)) {
                $statusCode = 500;

                if (method_exists($e, 'getStatusCode')) {
                    $statusCode = $e->getStatusCode();
                } elseif ($e instanceof AuthenticationException) {
                    $statusCode = 401;
                }

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'SERVER_ERROR',
                        'message' => config('app.debug') ? $e->getMessage() : 'حدث خطأ في الخادم',
                    ],
                ], $statusCode);
            }

            return null;
        });
    })->create();
