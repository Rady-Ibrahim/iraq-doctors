<?php

namespace Modules\Auth\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Auth\Models\User;
use App\Traits\ApiResponse;

class AdminUserController extends Controller
{
    use ApiResponse;

    /**
     * Get all users with filters
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = User::query();

            if ($request->has('role')) {
                $query->where('role', $request->role);
            }

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('search')) {
                $query->where(function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                      ->orWhere('phone', 'like', '%' . $request->search . '%')
                      ->orWhere('email', 'like', '%' . $request->search . '%');
                });
            }

            $users = $query->orderBy('created_at', 'desc')->paginate(20);

            return $this->success($users, 'تم جلب المستخدمين بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء جلب المستخدمين');
        }
    }

    /**
     * Get user details
     */
    public function show($id): JsonResponse
    {
        try {
            $user = User::with(['doctor', 'appointments'])->findOrFail($id);
            return $this->success($user, 'تم جلب تفاصيل المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->notFound('المستخدم غير موجود');
        }
    }

    /**
     * Block user
     */
    public function block($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->status = 'blocked';
            $user->save();
            return $this->success($user, 'تم حظر المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حظر المستخدم');
        }
    }

    /**
     * Unblock user
     */
    public function unblock($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->status = 'active';
            $user->save();
            return $this->success($user, 'تم فك الحظر عن المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء فك الحظر');
        }
    }

    /**
     * Delete user
     */
    public function destroy($id): JsonResponse
    {
        try {
            $user = User::findOrFail($id);
            $user->delete();
            return $this->success(null, 'تم حذف المستخدم بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('حدث خطأ أثناء حذف المستخدم');
        }
    }
}
