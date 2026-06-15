<?php

namespace Modules\Auth\Http\Controllers\Admin;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Auth\Http\Requests\Api\LoginRequest;
use Modules\Auth\Services\Api\AuthService;
use Modules\Auth\Models\User;
use App\Traits\ApiResponse;

class AdminAuthController extends Controller
{
    use ApiResponse;

    public function __construct(private AuthService $authService) {}

    /**
     * Dashboard login — admin and doctor only
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $user = $this->authService->login($request->phone, $request->password);

        if (!$user) {
            return $this->error('بيانات الدخول غير صحيحة', 'AUTH_INVALID_CREDENTIALS', 401);
        }

        // Dashboard is for admin and doctor only
        if (!in_array($user->role, ['admin', 'doctor'])) {
            return $this->error('غير مصرح لك بالدخول للوحة التحكم', 'AUTH_FORBIDDEN', 403);
        }

        $token = $this->authService->createToken($user);

        return $this->success([
            'user' => [
                'id'    => $user->id,
                'name'  => $user->name,
                'phone' => $user->phone,
                'email' => $user->email,
                'role'  => $user->role,
            ],
            'token' => $token,
        ], 'تم الدخول بنجاح');
    }

    /**
     * Get current dashboard user info
     */
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();

        if (!$user) {
            return $this->error('غير مصرح', 'UNAUTHENTICATED', 401);
        }

        if (!in_array($user->role, ['admin', 'doctor'])) {
            return $this->error('غير مصرح لك بالدخول للوحة التحكم', 'AUTH_FORBIDDEN', 403);
        }

        $data = [
            'id'     => $user->id,
            'name'   => $user->name,
            'phone'  => $user->phone,
            'email'  => $user->email,
            'role'   => $user->role,
            'status' => $user->status,
        ];

        if ($user->isDoctor()) {
            $data['doctor_stats'] = $this->authService->getDoctorStats($user);
        }

        return $this->success($data);
    }

    /**
     * Dashboard logout
     */
    public function logout(): JsonResponse
    {
        /** @var User|null $user */
        $user = auth('sanctum')->user();
        if ($user) {
            $user->tokens()->delete();
        }

        return $this->success(null, 'تم تسجيل الخروج بنجاح');
    }
}
