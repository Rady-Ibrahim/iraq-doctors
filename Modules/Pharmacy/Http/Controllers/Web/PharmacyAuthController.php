<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use App\Services\OtpSmsSender;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Doctor\Models\Governorate;
use Modules\Pharmacy\Http\Requests\Web\LoginPharmacyRequest;
use Modules\Pharmacy\Http\Requests\Web\RegisterPharmacyRequest;
use Modules\Pharmacy\Http\Requests\Web\VerifyPharmacyPhoneRequest;
use Modules\Pharmacy\Services\Web\PharmacyAuthService;
use RuntimeException;

class PharmacyAuthController extends Controller
{
    public function __construct(private PharmacyAuthService $pharmacyAuthService) {}

    public function showLogin(): View
    {
        return view('pharmacy.login');
    }

    public function login(LoginPharmacyRequest $request): RedirectResponse
    {
        $user = $this->pharmacyAuthService->login(
            $request->phone,
            $request->password
        );

        if (! $user) {
            return back()
                ->withInput($request->only('phone'))
                ->withErrors(['phone' => 'بيانات الدخول غير صحيحة']);
        }

        Auth::guard('web')->login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        if ($this->pharmacyAuthService->needsPhoneVerification($user)) {
            return redirect()
                ->route('pharmacy.verify-phone')
                ->with('info', 'يرجى تفعيل رقم هاتفك عبر كود واتساب.');
        }

        return redirect()->route($this->pharmacyAuthService->getPostLoginRoute($user));
    }

    public function showRegister(): View
    {
        $governorates = Governorate::where('is_active', true)->orderBy('name_ar')->get();

        return view('pharmacy.register', compact('governorates'));
    }

    public function register(RegisterPharmacyRequest $request): RedirectResponse
    {
        $user = $this->pharmacyAuthService->register(
            $request->validated(),
            $request->file('logo'),
            $request->file('commercial_register_document'),
            $request->file('license_document'),
            $request->file('owner_id_document')
        );

        Auth::guard('web')->login($user);

        return redirect()
            ->route('pharmacy.verify-phone')
            ->with('success', 'تم إنشاء حساب الصيدلية بنجاح. فعّل رقم هاتفك عبر كود واتساب.');
    }

    public function showVerifyPhone(): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $this->pharmacyAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->pharmacyAuthService->getPostLoginRoute($user));
        }

        try {
            $phoneE164 = PhoneNormalizer::toE164($user->phone);
        } catch (InvalidArgumentException) {
            return redirect()->route('pharmacy.logout')
                ->withErrors(['phone' => 'رقم الهاتف المسجل في حسابك غير صالح. تواصل مع الدعم.']);
        }

        return view('pharmacy.verify-phone', [
            'phoneE164' => $phoneE164,
            'maskedPhone' => PhoneNormalizer::mask($phoneE164),
            'otpConfigured' => $this->pharmacyAuthService->isOtpDeliveryConfigured(),
            'wasenderReady' => app(OtpSmsSender::class)->isConfigured(),
        ]);
    }

    public function sendPhoneOtp(): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $this->pharmacyAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->pharmacyAuthService->getPostLoginRoute($user));
        }

        try {
            $this->pharmacyAuthService->sendPhoneVerificationOtp($user);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إرسال كود التحقق عبر واتساب.');
    }

    public function verifyPhone(VerifyPharmacyPhoneRequest $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $this->pharmacyAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->pharmacyAuthService->getPostLoginRoute($user));
        }

        try {
            $this->pharmacyAuthService->verifyPhoneWithOtp($user, $request->code);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()
            ->route('pharmacy.pending')
            ->with('success', 'تم تفعيل رقم هاتفك. حساب الصيدلية قيد المراجعة من قبل الإدارة.');
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request = request();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('pharmacy.login');
    }
}
