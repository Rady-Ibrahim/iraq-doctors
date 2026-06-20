<?php

namespace Modules\Auth\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Doctor\Http\Requests\Web\LoginDoctorRequest;
use Modules\Auth\Services\Api\AuthService;

class AdminAuthController extends Controller
{
    public function __construct(private AuthService $authService) {}

    public function showLogin(): View
    {
        return view('admin.login');
    }

    public function login(LoginDoctorRequest $request): RedirectResponse
    {
        $user = $this->authService->login($request->phone, $request->password);

        if (!$user || !$user->isAdmin() || !$user->isActive()) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'بيانات الدخول غير صحيحة']);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));

        return redirect()->route('admin.dashboard');
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
