<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Log;
use Modules\Auth\Http\Requests\Api\RegisterRequest;
use Modules\Auth\Http\Requests\Api\LoginRequest;
use Modules\Auth\Http\Requests\Api\SendOtpRequest;
use Modules\Auth\Http\Requests\Api\VerifyOtpRequest;
use Modules\Auth\Http\Requests\Api\UpdateProfileRequest;
use Modules\Auth\Http\Requests\Api\UpdatePasswordRequest;
use Modules\Auth\Http\Requests\Api\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\Api\ResetPasswordRequest;
use Modules\Auth\Http\Requests\Api\CreateGhostPatientRequest;
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
                ],
            ], 'تم التسجيل بنجاح');
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
            $identifier = $request->email ?: $request->phone;

            $user = $this->authService->login(
                $identifier,
                $request->password
            );

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
            ],
            'token' => $token,
        ], 'تم الدخول بنجاح');
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $this->authService->sendOtp(
                null,
                $request->type,
                $request->email
            );
    
            return $this->success([
                'email' => $request->email,
                'type' => $request->type,
            ], 'تم إرسال الكود بنجاح');
    
        } catch (\Exception $e) {
            return $this->serverError($e->getMessage());
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $otp = $this->authService->verifyOtp(
            null,
            $request->code,
            $request->type,
            $request->email
        );
        
        if (!$otp) {
            return $this->error(
                'الكود غير صحيح أو منتهي الصلاحية',
                'OTP_INVALID',
                401
            );
        }
        
        $user = User::where('email', $request->email)->first();
        
        if ($user) {
            $user->update([
                'email_verified_at' => now(),
            ]);
        }
        
        return $this->success([
            'verified' => true,
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
            'id'        => $user->id,
            'name'      => $user->name,
            'avatar'    => $user->avatar ? asset('storage/' . $user->avatar) : null,
            'phone'     => $user->phone,
            'email'     => $user->email,
            'role'      => $user->role,
            'status'    => $user->status,
            'birthdate' => $user->birthdate,
            'gender'    => $user->gender,
            'city'      => $user->city,
            'district'  => $user->district,
            'address'   => $user->address,
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
            'id'        => $updatedUser->id,
            'name'      => $updatedUser->name,
            'avatar'    => $updatedUser->avatar ? asset('storage/' . $updatedUser->avatar) : null,
            'phone'     => $updatedUser->phone,
            'email'     => $updatedUser->email,
            'role'      => $updatedUser->role,
            'birthdate' => $updatedUser->birthdate,
            'gender'    => $updatedUser->gender,
            'city'      => $updatedUser->city,
            'district'  => $updatedUser->district,
            'address'   => $updatedUser->address,
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

        // Delete old avatar
        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $path = $request->file('avatar')->store('avatars', 'public');
        $user->update(['avatar' => $path]);

        return $this->success([
            'avatar' => asset('storage/' . $path),
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
        $sent = $this->authService->forgotPassword($request->phone);

        if (!$sent) {
            return $this->notFound('رقم الهاتف غير موجود');
        }

        return $this->success([
            'expires_in' => 600,
        ], 'تم إرسال كود التحقق إلى رقم الهاتف');
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

    public function createGhostPatient(CreateGhostPatientRequest $request): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if (!$user || !$user->isDoctor()) {
            return $this->forbidden('غير مصرح (الأطباء فقط)');
        }

        $patient = $this->authService->createGhostPatient($user, $request->validated());

        return $this->created([
            'id' => $patient->id,
            'name' => $patient->name,
            'phone' => $patient->phone,
            'is_ghost' => $patient->is_ghost,
        ], 'تم إضافة المريض بنجاح');
    }
}
