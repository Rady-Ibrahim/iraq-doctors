# 📚 دليل خطوة بخطوة - تطبيق الإصلاحات

**الهدف:** شرح مفصل لكيفية تطبيق كل إصلاح  
**المستوى:** للمطورين

---

## 🔥 الإصلاح #1: Error Handling & Logging

### الخطوة 1: إنشاء ApiResponse Trait

**الملف:** `app/Traits/ApiResponse.php`

```php
<?php

namespace App\Traits;

trait ApiResponse
{
    /**
     * Success response
     */
    public function success($data = null, $message = '', $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

    /**
     * Error response
     */
    public function error($message = '', $errorCode = 'ERROR', $statusCode = 400, $data = null)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
            ],
            'data' => $data,
        ], $statusCode);
    }

    /**
     * Paginated response
     */
    public function paginated($items, $total, $page, $limit, $message = '')
    {
        return response()->json([
            'success' => true,
            'data' => $items,
            'meta' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'last_page' => ceil($total / $limit),
            ],
            'message' => $message,
        ], 200);
    }
}
```

### الخطوة 2: تحديث Exception Handler

**الملف:** `app/Exceptions/Handler.php`

```php
<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Throwable;

class Handler extends ExceptionHandler
{
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Log all exceptions
            \Log::error('Exception occurred', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
                'url' => request()->fullUrl(),
                'user_id' => auth('sanctum')->id(),
            ]);
        });
    }

    public function render($request, Throwable $e)
    {
        if ($request->expectsJson()) {
            return $this->jsonResponse($e);
        }

        return parent::render($request, $e);
    }

    private function jsonResponse(Throwable $e): JsonResponse
    {
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;
        
        // في production، لا نظهر الرسالة الفعلية
        if (config('app.debug')) {
            $message = $e->getMessage();
        } else {
            $message = $statusCode === 500 ? 'حدث خطأ في الخادم' : $e->getMessage();
        }

        return response()->json([
            'success' => false,
            'error' => [
                'code' => class_basename($e),
                'message' => $message,
            ],
        ], $statusCode);
    }
}
```

### الخطوة 3: تحديث Controllers

**مثال:** `Modules/Doctor/Http/Controllers/Api/DoctorController.php`

```php
<?php

namespace Modules\Doctor\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use App\Traits\ApiResponse;  // ← أضف هذا
use Modules\Doctor\Http\Requests\Api\SearchDoctorsRequest;
use Modules\Doctor\Services\Api\DoctorService;

class DoctorController extends Controller
{
    use ApiResponse;  // ← أضف هذا

    public function __construct(private DoctorService $doctorService)
    {
    }

    public function index(SearchDoctorsRequest $request): JsonResponse
    {
        try {
            $query = $this->doctorService->search($request->validated());
            $limit = $request->limit ?? 20;
            $doctors = $query->paginate($limit);

            // استخدم الـ trait بدلاً من response()->json()
            return $this->paginated(
                $doctors->items(),
                $doctors->total(),
                $doctors->currentPage(),
                $limit
            );
        } catch (\Exception $e) {
            \Log::error('Failed to search doctors', [
                'error' => $e->getMessage(),
                'filters' => $request->validated(),
            ]);

            return $this->error('فشل البحث عن الأطباء', 'SEARCH_FAILED', 500);
        }
    }

    public function show(string $id): JsonResponse
    {
        try {
            $doctor = $this->doctorService->getProfile($id);

            if (!$doctor) {
                return $this->error('الطبيب غير موجود', 'DOCTOR_NOT_FOUND', 404);
            }

            return $this->success([
                'id' => $doctor->id,
                'name' => $doctor->user->name,
                'speciality' => [
                    'id' => $doctor->speciality->id,
                    'name_ar' => $doctor->speciality->name_ar,
                ],
                'bio' => $doctor->bio_ar,
                'experience_years' => $doctor->experience_years,
                'consultation_fee' => $doctor->consultation_fee,
                'rating' => $doctor->rating,
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to get doctor profile', [
                'doctor_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->error('فشل جلب بيانات الطبيب', 'FETCH_FAILED', 500);
        }
    }
}
```

### الخطوة 4: اختبر

```bash
# اختبر endpoint
curl -X GET http://localhost:8000/api/v1/doctors

# يجب أن ترى response بهذا الشكل:
{
  "success": true,
  "data": [...],
  "meta": {
    "page": 1,
    "limit": 20,
    "total": 50,
    "last_page": 3
  },
  "message": ""
}
```

---

## 🔐 الإصلاح #2: Authorization & Ownership Checks

### الخطوة 1: إنشاء Policies

**الملف:** `app/Policies/UserPolicy.php`

```php
<?php

namespace App\Policies;

use Modules\Auth\Models\User;

class UserPolicy
{
    public function update(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $authUser->isAdmin();
    }

    public function delete(User $authUser, User $user): bool
    {
        return $authUser->isAdmin();
    }

    public function view(User $authUser, User $user): bool
    {
        return $authUser->id === $user->id || $authUser->isAdmin();
    }

    public function block(User $authUser, User $user): bool
    {
        return $authUser->isAdmin();
    }
}
```

### الخطوة 2: تسجيل Policies

**الملف:** `app/Providers/AuthServiceProvider.php`

```php
<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Modules\Auth\Models\User;
use App\Policies\UserPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        User::class => UserPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
```

### الخطوة 3: استخدم في Controllers

**مثال:** `Modules/Auth/Http/Controllers/Api/AuthController.php`

```php
public function updateProfile(UpdateProfileRequest $request)
{
    $user = auth('sanctum')->user();
    
    // تحقق من الصلاحية
    $this->authorize('update', $user);
    
    try {
        $user->update($request->validated());
        
        \Log::info('Profile updated', ['user_id' => $user->id]);
        
        return $this->success($user, 'تم تحديث البيانات بنجاح');
    } catch (\Exception $e) {
        \Log::error('Failed to update profile', [
            'user_id' => $user->id,
            'error' => $e->getMessage(),
        ]);
        
        return $this->error('فشل تحديث البيانات', 'UPDATE_FAILED', 500);
    }
}
```

### الخطوة 4: اختبر

```bash
# محاولة تحديث بيانات user آخر (يجب أن يفشل)
curl -X PUT http://localhost:8000/api/v1/auth/profile \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"user_id": "OTHER_USER_ID", "name": "New Name"}'

# يجب أن ترى:
{
  "success": false,
  "error": {
    "code": "AuthorizationException",
    "message": "This action is unauthorized."
  }
}
```

---

## 🌐 الإصلاح #3: CORS & Security Headers

### الخطوة 1: إنشاء SecurityHeaders Middleware

**الملف:** `app/Http/Middleware/SecurityHeaders.php`

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SecurityHeaders
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        // Prevent clickjacking
        $response->header('X-Frame-Options', 'DENY');

        // Prevent MIME type sniffing
        $response->header('X-Content-Type-Options', 'nosniff');

        // Enable XSS protection
        $response->header('X-XSS-Protection', '1; mode=block');

        // Content Security Policy
        $response->header('Content-Security-Policy', "default-src 'self'");

        // Referrer Policy
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions Policy
        $response->header('Permissions-Policy', 'geolocation=(), microphone=(), camera=()');

        return $response;
    }
}
```

### الخطوة 2: تحديث bootstrap/app.php

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(prepend: [
        \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
    ]);

    // أضف Security Headers Middleware
    $middleware->api(append: [
        \App\Http\Middleware\SecurityHeaders::class,
    ]);

    $middleware->alias([
        'verified' => \App\Http\Middleware\EnsureEmailIsVerified::class,
        'role' => \App\Http\Middleware\RoleMiddleware::class,
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'doctor' => \App\Http\Middleware\DoctorMiddleware::class,
    ]);
})
```

### الخطوة 3: تحديث config/cors.php

```php
return [
    'paths' => ['api/*'],
    'allowed_methods' => ['*'],
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        env('ADMIN_URL', 'http://localhost:3001'),
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers' => ['*'],
    'exposed_headers' => [],
    'max_age' => 0,
    'supports_credentials' => true,
];
```

### الخطوة 4: اختبر

```bash
# اختبر CORS headers
curl -X OPTIONS http://localhost:8000/api/v1/doctors \
  -H "Origin: http://localhost:3000" \
  -v

# يجب أن ترى headers مثل:
# Access-Control-Allow-Origin: http://localhost:3000
# X-Frame-Options: DENY
# X-Content-Type-Options: nosniff
```

---

## ⚡ الإصلاح #4: Rate Limiting

### الخطوة 1: تحديث routes/api.php

```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    // Auth routes - special limits
    Route::post('/auth/register', [AuthController::class, 'register'])->withoutMiddleware('throttle');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:3,1');
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__.'/../Modules/Auth/Routes/api.php';
        require __DIR__.'/../Modules/Doctor/Routes/api.php';
        require __DIR__.'/../Modules/Appointment/Routes/api.php';
        require __DIR__.'/../Modules/Review/Routes/api.php';
        require __DIR__.'/../Modules/MedicalRecord/Routes/api.php';
        require __DIR__.'/../Modules/StaticPage/Routes/api.php';
        require __DIR__.'/../Modules/Subscription/Routes/api.php';
    });
});
```

### الخطوة 2: اختبر

```bash
# اختبر rate limiting بـ 5 طلبات متتالية
for i in {1..6}; do
  curl -X POST http://localhost:8000/api/v1/auth/login \
    -H "Content-Type: application/json" \
    -d '{"phone": "07123456789", "password": "password"}'
  echo "Request $i"
done

# الطلب السادس يجب أن يفشل مع:
# HTTP 429 Too Many Requests
```

---

## 📊 الإصلاح #5: Input Validation

### الخطوة 1: تحديث Requests

**مثال:** `Modules/Doctor/Http/Requests/Api/SearchDoctorsRequest.php`

```php
<?php

namespace Modules\Doctor\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class SearchDoctorsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'nullable|string|max:255',
            'speciality_id' => 'nullable|uuid|exists:specialities,id',
            'min_rating' => 'nullable|numeric|min:0|max:5',
            'max_rating' => 'nullable|numeric|min:0|max:5',
            'min_fee' => 'nullable|numeric|min:0|max:999999',
            'max_fee' => 'nullable|numeric|min:0|max:999999',
            'consultation_type' => 'nullable|in:clinic,home,online',
            'experience_level' => 'nullable|in:junior,intermediate,senior',
            'governorate' => 'nullable|string|max:100',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'distance_range' => 'nullable|numeric|min:1|max:100',
            'availability' => 'nullable|in:today,tomorrow,this_week',
            'sort_by' => 'nullable|in:rating,fee_asc,fee_desc,experience,distance',
            'limit' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ];
    }

    public function messages(): array
    {
        return [
            'name.max' => 'اسم الطبيب يجب أن لا يتجاوز 255 حرف',
            'speciality_id.exists' => 'التخصص المختار غير موجود',
            'min_rating.max' => 'التقييم يجب أن يكون بين 0 و 5',
            'latitude.between' => 'خط العرض يجب أن يكون بين -90 و 90',
            'longitude.between' => 'خط الطول يجب أن يكون بين -180 و 180',
            'distance_range.max' => 'نطاق المسافة يجب أن لا يتجاوز 100 كم',
            'limit.max' => 'الحد الأقصى للنتائج هو 100',
        ];
    }
}
```

### الخطوة 2: اختبر

```bash
# اختبر validation
curl -X GET "http://localhost:8000/api/v1/doctors?min_rating=10" \
  -H "Content-Type: application/json"

# يجب أن ترى:
{
  "success": false,
  "error": {
    "code": "ValidationException",
    "message": "التقييم يجب أن يكون بين 0 و 5"
  }
}
```

---

## 🎯 الخطوات التالية

بعد إصلاح جميع المشاكل الحرجة:

1. **اختبر شامل** - اختبر جميع الـ endpoints
2. **ابدأ بـ Dashboard Pages** - اتبع `DASHBOARD_PAGES_TODO.md`
3. **اختبر الداشبورد** - اختبر جميع الصفحات
4. **الإطلاق** - اتبع `PRODUCTION_CHECKLIST.md`

---

**الوقت المتوقع:** 5-7 أيام  
**الهدف:** إصلاح جميع المشاكل الحرجة  
**النجاح:** اتبع الخطوات بالترتيب!
