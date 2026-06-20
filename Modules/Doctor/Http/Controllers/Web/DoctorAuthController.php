<?php

namespace Modules\Doctor\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Doctor\Http\Requests\Web\LoginDoctorRequest;
use Modules\Doctor\Http\Requests\Web\RegisterDoctorRequest;
use Modules\Doctor\Models\Speciality;
use Modules\Doctor\Services\Web\DoctorAuthService;

class DoctorAuthController extends Controller
{
    public function __construct(private DoctorAuthService $doctorAuthService) {}

    public function showLogin(): View
    {
        return view('doctor.login');
    }

    public function login(LoginDoctorRequest $request): RedirectResponse
    {
        $user = $this->doctorAuthService->login(
            $request->phone,
            $request->password
        );

        if (!$user) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'بيانات الدخول غير صحيحة']);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));

        return redirect()->route('doctor.dashboard');
    }

    public function showRegister(): View
    {
        $specialities = Speciality::where('is_active', true)->orderBy('name_ar')->get();

        return view('doctor.register', compact('specialities'));
    }

    public function register(RegisterDoctorRequest $request): RedirectResponse
    {
        $user = $this->doctorAuthService->register(
            $request->validated(),
            $request->file('license_document'),
            $request->file('clinic_image')
        );

        Auth::guard('web')->login($user);

        return redirect()
            ->route('doctor.dashboard')
            ->with('success', 'تم إنشاء حسابك بنجاح. حسابك قيد المراجعة من قبل الإدارة.');
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request = request();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('doctor.login');
    }
}
