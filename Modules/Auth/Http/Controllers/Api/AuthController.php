<?php

namespace Modules\Auth\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\Api\RegisterRequest;
use Modules\Auth\Http\Requests\Api\LoginRequest;
use Modules\Auth\Http\Requests\Api\SendOtpRequest;
use Modules\Auth\Http\Requests\Api\VerifyOtpRequest;
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

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role' => $user->role,
                'status' => $user->status,
            ],
        ], 200);
    }
}
