<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\Api\RegisterRequest;
use Modules\Auth\Http\Requests\Api\LoginRequest;
use Modules\Auth\Http\Requests\Api\SendOtpRequest;
use Modules\Auth\Http\Requests\Api\VerifyOtpRequest;
use Modules\Auth\Http\Requests\Api\UpdateProfileRequest;
use Modules\Auth\Http\Requests\Api\UpdatePasswordRequest;
use Modules\Auth\Http\Requests\Api\ForgotPasswordRequest;
use Modules\Auth\Http\Requests\Api\ResetPasswordRequest;
use Modules\Auth\Http\Requests\Api\CreateGhostPatientRequest;
use Modules\Auth\Services\Api\AuthService;

class AuthController extends Controller
{
    public function __construct(private AuthService $authService)
    {
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());
            $token = $this->authService->createToken($user);

            return response()->json([
                'success' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'phone' => $user->phone,
                        'email' => $user->email,
                        'role' => $user->role,
                    ],
                    'token' => $token,
                ],
                'message' => 'تم التسجيل بنجاح',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REGISTRATION_FAILED',
                    'message' => 'فشل التسجيل',
                ],
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->phone, $request->password);

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'AUTH_INVALID_CREDENTIALS',
                    'message' => 'بيانات الدخول غير صحيحة',
                ],
            ], 401);
        }

        $token = $this->authService->createToken($user);

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'phone' => $user->phone,
                    'email' => $user->email,
                    'role' => $user->role,
                ],
                'token' => $token,
            ],
            'message' => 'تم الدخول بنجاح',
        ], 200);
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        try {
            $this->authService->sendOtp($request->phone, $request->type);

            return response()->json([
                'success' => true,
                'data' => [
                    'phone' => $request->phone,
                    'type' => $request->type,
                ],
                'message' => 'تم إرسال الكود بنجاح',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'OTP_SEND_FAILED',
                    'message' => 'فشل إرسال الكود',
                ],
            ], 500);
        }
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $otp = $this->authService->verifyOtp($request->phone, $request->code, $request->type);

        if (!$otp) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'OTP_INVALID',
                    'message' => 'الكود غير صحيح أو منتهي الصلاحية',
                ],
            ], 401);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'phone' => $request->phone,
                'type' => $request->type,
                'verified' => true,
            ],
            'message' => 'تم التحقق بنجاح',
        ], 200);
    }

    public function logout(): JsonResponse
    {
        auth('sanctum')->user()->tokens()->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم تسجيل الخروج بنجاح',
        ], 200);
    }

    public function me(): JsonResponse
    {
        $user = auth('sanctum')->user();

        $data = [
            'id' => $user->id,
            'name' => $user->name,
            'phone' => $user->phone,
            'email' => $user->email,
            'role' => $user->role,
            'status' => $user->status,
        ];

        if ($user->isDoctor()) {
            $data['doctor_stats'] = $this->authService->getDoctorStats($user);
        }

        return response()->json([
            'success' => true,
            'data' => $data,
        ], 200);
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $updatedUser = $this->authService->updateProfile($user, $request->validated());

        $response = [
            'id' => $updatedUser->id,
            'name' => $updatedUser->name,
            'phone' => $updatedUser->phone,
            'email' => $updatedUser->email,
            'role' => $updatedUser->role,
        ];

        if ($updatedUser->isDoctor()) {
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $updatedUser->id)->first();
            if ($doctor) {
                $response['bio'] = $doctor->bio_ar;
                $response['experience_years'] = $doctor->experience_years;
            }
        }

        return response()->json([
            'success' => true,
            'data' => $response,
            'message' => 'تم تحديث البيانات الشخصية بنجاح',
        ], 200);
    }

    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();
        $this->authService->updatePassword($user, $request->new_password);

        return response()->json([
            'success' => true,
            'message' => 'تم تغيير كلمة المرور بنجاح',
        ], 200);
    }

    public function forgotPassword(ForgotPasswordRequest $request): JsonResponse
    {
        $sent = $this->authService->forgotPassword($request->phone);

        if (!$sent) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'USER_NOT_FOUND',
                    'message' => 'رقم الهاتف غير موجود',
                ],
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال كود التحقق إلى رقم الهاتف',
            'expires_in' => 600,
        ], 200);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $user = $this->authService->resetPassword(
            $request->phone,
            $request->code,
            $request->new_password
        );

        if (!$user) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RESET_FAILED',
                    'message' => 'كود التحقق غير صحيح أو منتهي الصلاحية',
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة تعيين كلمة المرور بنجاح',
        ], 200);
    }

    public function createGhostPatient(CreateGhostPatientRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user || !$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح (الأطباء فقط)',
                ],
            ], 403);
        }

        $patient = $this->authService->createGhostPatient($user, $request->validated());

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $patient->id,
                'name' => $patient->name,
                'phone' => $patient->phone,
                'is_ghost' => $patient->is_ghost,
            ],
            'message' => 'تم إضافة المريض بنجاح',
        ], 201);
    }
}
