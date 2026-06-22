<?php

namespace Modules\Doctor\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Modules\Doctor\Http\Requests\Web\LoginDoctorRequest;
use Modules\Doctor\Http\Requests\Web\RegisterDoctorRequest;
use Modules\Doctor\Http\Requests\Web\VerifyDoctorEmailRequest;
use Modules\Doctor\Models\Governorate;
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

        if (!$user->email_verified_at) {
            $this->doctorAuthService->sendVerificationOtp($user);

            return redirect()
                ->route('doctor.verify-email')
                ->with('info', 'تم إرسال كود التفعيل إلى بريدك الإلكتروني.');
        }

        return redirect()->route($this->doctorAuthService->getPostLoginRoute($user));
    }

    public function showRegister(): View
    {
        $specialities = Speciality::where('is_active', true)->orderBy('name_ar')->get();
        $governorates = Governorate::where('is_active', true)->orderBy('name_ar')->get();

        return view('doctor.register', compact('specialities', 'governorates'));
    }

    public function register(RegisterDoctorRequest $request): RedirectResponse
    {
        $user = $this->doctorAuthService->register(
            $request->validated(),
            $request->file('license_document'),
            $request->file('clinic_image'),
            $request->file('avatar')
        );

        Auth::guard('web')->login($user);

        $this->doctorAuthService->sendVerificationOtp($user);

        return redirect()
            ->route('doctor.verify-email')
            ->with('success', 'تم إنشاء حسابك بنجاح. أدخل كود التفعيل المرسل إلى بريدك الإلكتروني.');
    }

    public function showVerifyEmail(): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user->email_verified_at) {
            return redirect()->route($this->doctorAuthService->getPostLoginRoute($user));
        }

        return view('doctor.verify-email', ['email' => $user->email]);
    }

    public function verifyEmail(VerifyDoctorEmailRequest $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user->email_verified_at) {
            return redirect()->route($this->doctorAuthService->getPostLoginRoute($user));
        }

        if (!$this->doctorAuthService->verifyEmail($user, $request->code)) {
            return back()->withErrors(['code' => 'كود التفعيل غير صحيح أو منتهي الصلاحية']);
        }

        return redirect()
            ->route('doctor.pending')
            ->with('success', 'تم تفعيل بريدك الإلكتروني. حسابك قيد المراجعة من قبل الإدارة.');
    }

    public function resendVerificationOtp(): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if ($user->email_verified_at) {
            return redirect()->route($this->doctorAuthService->getPostLoginRoute($user));
        }

        $this->doctorAuthService->sendVerificationOtp($user);

        return back()->with('info', 'تم إعادة إرسال كود التفعيل إلى بريدك الإلكتروني.');
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
