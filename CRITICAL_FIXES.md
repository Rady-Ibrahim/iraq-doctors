# 🔥 الإصلاحات الحرجة - Iraq Doctors Platform

**الأولوية:** Critical - يجب إصلاح هذه الأشياء قبل الإطلاق

---

## 1. API Response Format Consistency

### المشكلة:
```php
// بعض الـ responses:
return response()->json([
    'success' => true,
    'data' => $data,
    'message' => 'نجح'
], 200);

// وبعضها:
return response()->json([
    'success' => false,
    'error' => ['code' => 'ERROR', 'message' => 'فشل']
], 400);
```

### الحل:
إنشاء Trait للـ API responses:

```php
// app/Traits/ApiResponse.php
<?php

namespace App\Traits;

trait ApiResponse
{
    public function success($data = null, $message = '', $code = 200)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'message' => $message,
        ], $code);
    }

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

### الاستخدام:
```php
class DoctorController extends Controller
{
    use ApiResponse;

    public function index()
    {
        $doctors = Doctor::paginate(20);
        return $this->paginated(
            $doctors->items(),
            $doctors->total(),
            $doctors->currentPage(),
            20
        );
    }

    public function show($id)
    {
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return $this->error('الطبيب غير موجود', 'DOCTOR_NOT_FOUND', 404);
        }
        return $this->success($doctor);
    }
}
```

---

## 2. Error Handling & Logging

### المشكلة:
```php
try {
    // code
} catch (\Exception $e) {
    return response()->json([
        'success' => false,
        'error' => $e->getMessage() // قد يظهر sensitive info
    ], 500);
}
```

### الحل:

**أ) تحديث Exception Handler:**
```php
// app/Exceptions/Handler.php
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

**ب) استخدام في Controllers:**
```php
class DoctorController extends Controller
{
    use ApiResponse;

    public function store(CreateDoctorRequest $request)
    {
        try {
            $doctor = Doctor::create($request->validated());
            
            \Log::info('Doctor created', ['doctor_id' => $doctor->id]);
            
            return $this->success($doctor, 'تم إنشاء الطبيب بنجاح', 201);
        } catch (\Exception $e) {
            \Log::error('Failed to create doctor', [
                'error' => $e->getMessage(),
                'input' => $request->validated(),
            ]);
            
            return $this->error('فشل إنشاء الطبيب', 'CREATION_FAILED', 500);
        }
    }
}
```

---

## 3. Authorization & Ownership Checks

### المشكلة:
```php
// أي user يمكنه تعديل بيانات أي user آخر
public function updateProfile(UpdateProfileRequest $request)
{
    $user = User::find($request->user_id); // خطر!
    $user->update($request->validated());
    return $this->success($user);
}
```

### الحل:

**أ) إنشاء Policy:**
```php
// app/Policies/UserPolicy.php
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
}
```

**ب) استخدام في Controller:**
```php
class AuthController extends Controller
{
    use ApiResponse;

    public function updateProfile(UpdateProfileRequest $request)
    {
        $user = auth('sanctum')->user();
        
        // التحقق من الصلاحية
        $this->authorize('update', $user);
        
        $user->update($request->validated());
        
        \Log::info('Profile updated', ['user_id' => $user->id]);
        
        return $this->success($user, 'تم تحديث البيانات بنجاح');
    }

    public function deleteUser(string $userId)
    {
        $user = User::findOrFail($userId);
        
        // التحقق من أن المستخدم هو admin
        if (!auth('sanctum')->user()->isAdmin()) {
            return $this->error('غير مصرح', 'UNAUTHORIZED', 403);
        }
        
        $user->delete();
        
        \Log::info('User deleted', ['user_id' => $userId, 'deleted_by' => auth('sanctum')->user()->id]);
        
        return $this->success(null, 'تم حذف المستخدم بنجاح');
    }
}
```

---

## 4. Input Validation & Sanitization

### المشكلة:
```php
// Validation ناقصة
class SearchDoctorsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'name' => 'string',
            'min_fee' => 'numeric',
        ];
    }
}
```

### الحل:
```php
// Modules/Doctor/Http/Requests/Api/SearchDoctorsRequest.php
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

---

## 5. Database Transactions

### المشكلة:
```php
// قد تحدث inconsistencies
public function bookAppointment($data)
{
    $appointment = Appointment::create($data);
    $doctor->increment('appointment_count'); // قد يفشل
    return $appointment;
}
```

### الحل:
```php
// Modules/Appointment/Services/Api/AppointmentService.php
<?php

namespace Modules\Appointment\Services\Api;

use Illuminate\Support\Facades\DB;
use Modules\Appointment\Models\Appointment;

class AppointmentService
{
    public function book(array $data): Appointment
    {
        return DB::transaction(function () use ($data) {
            // 1. تحقق من التوفر
            $isAvailable = $this->checkAvailability(
                $data['doctor_id'],
                $data['appointment_date'],
                $data['appointment_time']
            );

            if (!$isAvailable) {
                throw new \Exception('الموعد غير متاح');
            }

            // 2. إنشاء الموعد
            $appointment = Appointment::create($data);

            // 3. تحديث إحصائيات الطبيب
            Doctor::where('id', $data['doctor_id'])
                ->increment('total_appointments');

            // 4. تسجيل النشاط
            \Log::info('Appointment booked', [
                'appointment_id' => $appointment->id,
                'doctor_id' => $data['doctor_id'],
                'patient_id' => $data['patient_id'],
            ]);

            return $appointment;
        }, attempts: 3); // إعادة المحاولة 3 مرات في حالة الفشل
    }
}
```

---

## 6. CORS & Security Headers

### المشكلة:
لا توجد CORS configuration

### الحل:

**أ) تحديث bootstrap/app.php:**
```php
return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(...)
    ->withMiddleware(function (Middleware $middleware) {
        // CORS
        $middleware->api(prepend: [
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
        ]);

        // Security Headers
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
    ->create();
```

**ب) إنشاء Security Headers Middleware:**
```php
// app/Http/Middleware/SecurityHeaders.php
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

**ج) تحديث config/cors.php:**
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

---

## 7. Rate Limiting

### المشكلة:
لا توجد protection من abuse

### الحل:

**أ) تحديث routes/api.php:**
```php
<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('throttle:60,1')->group(function () {
    // Public routes
    Route::post('/auth/register', [AuthController::class, 'register'])->withoutMiddleware('throttle');
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('/auth/send-otp', [AuthController::class, 'sendOtp'])->middleware('throttle:3,1');
    
    // Protected routes
    Route::middleware('auth:sanctum')->group(function () {
        require __DIR__.'/../Modules/Auth/Routes/api.php';
        require __DIR__.'/../Modules/Doctor/Routes/api.php';
        // ... other routes
    });
});
```

**ب) إنشاء Custom Throttle Middleware:**
```php
// app/Http/Middleware/CustomThrottle.php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiter;

class CustomThrottle
{
    public function handle(Request $request, Closure $next)
    {
        $key = $this->resolveRequestSignature($request);
        $maxAttempts = 60;
        $decayMinutes = 1;

        if ($this->limiter->tooManyAttempts($key, $maxAttempts)) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RATE_LIMIT_EXCEEDED',
                    'message' => 'تم تجاوز حد الطلبات المسموح',
                    'retry_after' => $this->limiter->availableIn($key),
                ],
            ], 429);
        }

        $this->limiter->hit($key, $decayMinutes * 60);

        return $next($request);
    }

    protected function resolveRequestSignature(Request $request)
    {
        return sha1(implode('|', [
            $request->method(),
            $request->getHost(),
            $request->user()?->id ?: $request->ip(),
        ]));
    }
}
```

---

## 📋 خطوات التطبيق

1. **نسخ الـ Traits والـ Middleware:**
   - `app/Traits/ApiResponse.php`
   - `app/Http/Middleware/SecurityHeaders.php`
   - `app/Http/Middleware/CustomThrottle.php`

2. **تحديث جميع Controllers:**
   - إضافة `use ApiResponse;`
   - استبدال `response()->json()` بـ `$this->success()` و `$this->error()`

3. **تحديث جميع Requests:**
   - إضافة proper validation rules
   - إضافة custom messages

4. **تحديث Exception Handler:**
   - نسخ الكود الجديد

5. **تحديث Routes:**
   - إضافة rate limiting
   - إضافة security headers

6. **اختبار:**
   - اختبار جميع الـ endpoints
   - اختبار الـ error handling
   - اختبار الـ rate limiting

---

**الأولوية:** يجب إصلاح هذه الأشياء قبل الإطلاق للإنتاج!
