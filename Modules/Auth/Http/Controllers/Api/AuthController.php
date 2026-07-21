<?php

namespace Modules\Auth\Http\Controllers\Api;

use App\Support\PhoneNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;
use Modules\Auth\Http\Requests\Api\RegisterRequest;
use Modules\Auth\Http\Requests\Api\LoginRequest;
use Modules\Auth\Http\Requests\Api\SendOtpRequest;
use Modules\Auth\Http\Requests\Api\VerifyOtpRequest;
use Modules\Auth\Http\Requests\Api\UpdateProfileRequest;
use Modules\Auth\Http\Requests\Api\UpdatePasswordRequest;
use Modules\Auth\Http\Requests\Api\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\Api\ResetPasswordRequest;
use Modules\Auth\Http\Requests\Api\UploadAvatarRequest;
use Modules\Auth\Services\Api\AuthService;
use Modules\Auth\Models\User;
use App\Traits\ApiResponse;
use Illuminate\Support\Facades\Storage;

class AuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return $this->created([
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                    'phone_verified_at' => $user->phone_verified_at,
                ],
                'requires_phone_verification' => true,
            ], 'تم التسجيل بنجاح. فعّل رقم الهاتف عبر send-otp قبل تسجيل الدخول');
        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage(), [
                'exception' => $e,
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->serverError('فشل التسجيل');
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = $request->phone;

        try {
            PhoneNormalizer::toE164($identifier);
        } catch (InvalidArgumentException) {
            return $this->error('رقم الهاتف غير صحيح', 'INVALID_PHONE', 422);
        }

        try {
            $user = $this->authService->login($identifier, $request->password);
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'PHONE_NOT_VERIFIED') {
                return $this->error(
                    'يرجى تفعيل رقم الهاتف أولاً عبر كود واتساب',
                    'PHONE_NOT_VERIFIED',
                    403
                );
            }

            throw $e;
        }

        if (!$user) {
            return $this->error(
                'بيانات الدخول غير صحيحة',
                'AUTH_INVALID_CREDENTIALS',
                401
            );
        }

        $token = $this->authService->createToken($user);

        return $this->success([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'phone_verified_at' => $user->phone_verified_at,
            ],
            'token' => $token,
        ], 'تم الدخول بنجاح');
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $otp = $this->authService->sendOtp($request->phone, $request->type);
            $phone = PhoneNormalizer::toE164($request->phone);

            $payload = [
                'phone' => $phone,
                'type' => $request->type,
                'expires_in' => (int) config('otp.expires_minutes', 10) * 60,
            ];

            if ($this->authService->shouldExposeOtpCode()) {
                $payload['code'] = $otp->code;
            }

            return $this->success($payload, 'تم إرسال الكود بنجاح');
        } catch (InvalidArgumentException $e) {
            if ($e->getMessage() === 'USER_NOT_FOUND') {
                return $this->error('لا يوجد حساب بهذا الرقم', 'USER_NOT_FOUND', 404);
            }

            return $this->error($e->getMessage(), 'INVALID_PHONE', 422);
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        try {
            $phone = PhoneNormalizer::toE164($request->phone);
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'INVALID_PHONE', 422);
        }

        $type = $request->type;

        $otp = $this->authService->verifyOtp(
            $request->phone,
            $request->code,
            $type
        );

        if (!$otp) {
            return $this->error(
                'الكود غير صحيح أو منتهي الصلاحية',
                'OTP_INVALID',
                401
            );
        }

        $variants = PhoneNormalizer::lookupVariants($phone);
        $user = User::query()->whereIn('phone', $variants)->first();

        // OTP Login: تسجيل دخول مباشر بعد التحقق من الكود
        if ($type === 'login') {
            if (!$user) {
                return $this->error('المستخدم غير موجود', 'USER_NOT_FOUND', 404);
            }

            if (!$user->isActive()) {
                return $this->error('الحساب موقوف', 'ACCOUNT_INACTIVE', 403);
            }

            // تفعيل الهاتف تلقائياً إذا لم يكن مُفعَّلاً بعد
            if (!$user->phone_verified_at) {
                $this->authService->markPhoneVerifiedForUser($phone);
                $user = $user->fresh();
            }

            $token = $this->authService->createToken($user);

            return $this->success([
                'verified' => true,
                'token'    => $token,
                'user'     => [
                    'id'                => $user->id,
                    'name'              => $user->name,
                    'phone'             => $user->phone,
                    'email'             => $user->email,
                    'role'              => $user->role,
                    'phone_verified_at' => $user->phone_verified_at,
                ],
            ], 'تم تسجيل الدخول بنجاح');
        }

        // phone_verify / password_reset: تأكيد فقط بدون token
        return $this->success([
            'verified'          => true,
            'phone'             => $phone,
            'user_exists'       => (bool) $user,
            'phone_verified_at' => $user?->fresh()->phone_verified_at,
        ], 'تم التحقق بنجاح');
    }

    public function logout(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if ($user) {
            $user->tokens()->delete();
        }

        return $this->success(null, 'تم تسجيل الخروج بنجاح');
    }

    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->error('المستخدم غير موجود', 'USER_NOT_FOUND', 404);
        }

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'avatar' => storage_public_url($user->avatar),
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
            'birthdate' => $user->birthdate,
            'gender' => $user->gender,
            'city' => $user->city,
            'district' => $user->district,
            'address' => $user->address,
        ];

        if ($user->isDoctor()) {
            $data['doctor_stats'] = $this->authService->getDoctorStats($user);
        }

        return $this->success($data);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->error('المستخدم غير موجود', 'USER_NOT_FOUND', 404);
        }

        $updatedUser = $this->authService->updateProfile($user, $request->validated());

        $response = [
            'id' => $updatedUser->id,
            'name' => $updatedUser->name,
            'avatar' => storage_public_url($updatedUser->avatar),
            'phone' => $updatedUser->phone,
            'email' => $updatedUser->email,
            'role' => $updatedUser->role,
            'birthdate' => $updatedUser->birthdate,
            'gender' => $updatedUser->gender,
            'city' => $updatedUser->city,
            'district' => $updatedUser->district,
            'address' => $updatedUser->address,
        ];

        if ($updatedUser->isDoctor()) {
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $updatedUser->id)->first();
            if ($doctor) {
                $response['bio'] = $doctor->bio_ar;
                $response['experience_years'] = $doctor->experience_years;
            }
        }

        return $this->success($response, 'تم تحديث البيانات الشخصية بنجاح');
    }

    public function uploadAvatar(UploadAvatarRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->error('المستخدم غير موجود', 'USER_NOT_FOUND', 404);
        }

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar' => storage_public_url($path),
        ], 'تم تحديث الصورة الشخصية بنجاح');
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $this->authService->updatePassword($user, $request->new_password);

        return $this->success(null, 'تم تغيير كلمة المرور بنجاح');
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        try {
            $sent = $this->authService->forgotPassword($request->phone);

            if (!$sent) {
                return $this->notFound('رقم الهاتف غير موجود');
            }

            $payload = [
                'expires_in' => (int) config('otp.expires_minutes', 10) * 60,
            ];

            if ($this->authService->shouldExposeOtpCode()) {
                $phone = PhoneNormalizer::toE164($request->phone);
                $latest = \Modules\Auth\Models\Otp::query()
                    ->where('phone', $phone)
                    ->where('type', 'password_reset')
                    ->latest()
                    ->first();
                if ($latest) {
                    $payload['code'] = $latest->code;
                }
            }

            return $this->success($payload, 'تم إرسال كود التحقق إلى رقم الهاتف');
        } catch (InvalidArgumentException $e) {
            return $this->error($e->getMessage(), 'INVALID_PHONE', 422);
        }
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resetPassword(
            $request->phone,
            $request->code,
            $request->new_password
        );

        if (!$user) {
            return $this->error('كود التحقق غير صحيح أو منتهي الصلاحية', 'RESET_FAILED', 400);
        }

        return $this->success(null, 'تم إعادة تعيين كلمة المرور بنجاح');
    }
}
