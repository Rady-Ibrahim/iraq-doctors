<?php

namespace Modules\Doctor\Http\Controllers\Web;

use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Doctor\Http\Requests\Web\LoginDoctorRequest;
use Modules\Doctor\Http\Requests\Web\RegisterDoctorRequest;
use Modules\Doctor\Http\Requests\Web\VerifyDoctorPhoneRequest;
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

        if ($this->doctorAuthService->needsPhoneVerification($user)) {
            return redirect()
                ->route('doctor.verify-phone')
                ->with('info', 'يرجى تفعيل رقم هاتفك عبر كود SMS.');
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

        return redirect()
            ->route('doctor.verify-phone')
            ->with('success', 'تم إنشاء حسابك بنجاح. فعّل رقم هاتفك عبر كود SMS.');
    }

    public function showVerifyPhone(): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (!$this->doctorAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->doctorAuthService->getPostLoginRoute($user));
        }

        try {
            $phoneE164 = PhoneNormalizer::toE164($user->phone);
        } catch (InvalidArgumentException) {
            return redirect()->route('doctor.logout')
                ->withErrors(['phone' => 'رقم الهاتف المسجل في حسابك غير صالح. تواصل مع الدعم.']);
        }

        return view('doctor.verify-phone', [
            'phoneE164' => $phoneE164,
            'maskedPhone' => PhoneNormalizer::mask($phoneE164),
            'firebaseConfigured' => $this->doctorAuthService->isFirebaseWebConfigured(),
        ]);
    }

    public function verifyPhone(VerifyDoctorPhoneRequest $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (!$this->doctorAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->doctorAuthService->getPostLoginRoute($user));
        }

        try {
            $this->doctorAuthService->verifyPhoneWithFirebaseToken($user, $request->firebase_token);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['firebase_token' => $e->getMessage()]);
        }

        return redirect()
            ->route('doctor.pending')
            ->with('success', 'تم تفعيل رقم هاتفك. حسابك قيد المراجعة من قبل الإدارة.');
    }

    /** @deprecated redirect legacy email verification URL */
    public function showVerifyEmail(): RedirectResponse
    {
        return redirect()->route('doctor.verify-phone');
    }

    /** @deprecated */
    public function verifyEmail(): RedirectResponse
    {
        return redirect()->route('doctor.verify-phone');
    }

    /** @deprecated */
    public function resendVerificationOtp(): RedirectResponse
    {
        return redirect()->route('doctor.verify-phone')
            ->with('info', 'استخدم زر إرسال كود SMS لتفعيل رقم هاتفك.');
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
