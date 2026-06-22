<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Request;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        api: __DIR__.'/../routes/api.php',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->redirectGuestsTo(function (Request $request) {
            if ($request->is('doctor/*')) {
                return route('doctor.login');
            }

            if ($request->is('admin/*')) {
                return route('admin.login');
            }

            return '/login';
        });

        // تسجيل الـ Middleware الـ Aliases الخاصة بالسيستم
        $middleware->alias([
            'admin'            => \App\Http\Middleware\AdminMiddleware::class,
            'doctor'           => \App\Http\Middleware\DoctorMiddleware::class,
            'doctor.approved'  => \App\Http\Middleware\DoctorApprovedMiddleware::class,
            'doctor.email.verified' => \App\Http\Middleware\DoctorEmailVerifiedMiddleware::class,
            'role'             => \App\Http\Middleware\RoleMiddleware::class, // 🌟 تم إضافة الرول ميدل وير هنا
            'security.headers' => \App\Http\Middleware\SecurityHeaders::class,
        ]);

        // تطبيق حماية الـ Security Headers على كل مسارات الـ API أوتوماتيك
        $middleware->api(append: [
            \App\Http\Middleware\SecurityHeaders::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        
        // 🎯 الفصل الذكي والمستقر في معالجة الأخطاء
        $exceptions->render(function (Throwable $e, Request $request) {
            
            // 1️⃣ لو الطلب جاي من الـ API (بوست مان / الموبايل) -> رجع JSON دايماً بنظافة
            if ($request->expectsJson() || $request->is('api/*')) {
                $statusCode = 500;
                
                if (method_exists($e, 'getStatusCode')) {
                    $statusCode = $e->getStatusCode();
                } elseif ($e instanceof AuthenticationException) {
                    $statusCode = 401;
                }

                return response()->json([
                    'success' => false,
                    'error' => [
                        'code'    => class_basename($e),
                        'message' => config('app.debug') ? $e->getMessage() : 'حدث خطأ في الخادممم',
                    ],
                ], $statusCode);
            }

            // 2️⃣ لو طلب ويب عادي (المتصفح) -> اترك لارافيل يكمل الـ Redirects وصفحات الـ HTML العادية
            return null;
        });
    })->create();