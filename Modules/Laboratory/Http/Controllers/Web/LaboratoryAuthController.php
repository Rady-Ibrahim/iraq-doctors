<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use App\Services\OtpSmsSender;
use App\Support\PhoneNormalizer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use InvalidArgumentException;
use Modules\Doctor\Models\Governorate;
use Modules\Laboratory\Http\Requests\Web\LoginLaboratoryRequest;
use Modules\Laboratory\Http\Requests\Web\RegisterLaboratoryRequest;
use Modules\Laboratory\Http\Requests\Web\VerifyLaboratoryPhoneRequest;
use Modules\Laboratory\Services\Web\LaboratoryAuthService;
use RuntimeException;

class LaboratoryAuthController extends Controller
{
    public function __construct(private LaboratoryAuthService $laboratoryAuthService) {}

    public function showLogin(): View
    {
        return view('laboratory.login');
    }

    public function login(LoginLaboratoryRequest $request): RedirectResponse
    {
        $user = $this->laboratoryAuthService->login(
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

        if ($this->laboratoryAuthService->needsPhoneVerification($user)) {
            return redirect()
                ->route('laboratory.verify-phone')
                ->with('info', 'يرجى تفعيل رقم هاتفك عبر كود واتساب.');
        }

        return redirect()->route($this->laboratoryAuthService->getPostLoginRoute($user));
    }

    public function showRegister(): View
    {
        $governorates = Governorate::where('is_active', true)->orderBy('name_ar')->get();

        return view('laboratory.register', compact('governorates'));
    }

    public function register(RegisterLaboratoryRequest $request): RedirectResponse
    {
        $user = $this->laboratoryAuthService->register(
            $request->validated(),
            $request->file('logo'),
            $request->file('commercial_register_document'),
            $request->file('license_document'),
            $request->file('owner_id_document'),
            $request->file('accreditation_document')
        );

        Auth::guard('web')->login($user);

        return redirect()
            ->route('laboratory.verify-phone')
            ->with('success', 'تم إنشاء حساب المختبر بنجاح. فعّل رقم هاتفك عبر كود واتساب.');
    }

    public function showVerifyPhone(): View|RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $this->laboratoryAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->laboratoryAuthService->getPostLoginRoute($user));
        }

        try {
            $phoneE164 = PhoneNormalizer::toE164($user->phone);
        } catch (InvalidArgumentException) {
            return redirect()->route('laboratory.logout')
                ->withErrors(['phone' => 'رقم الهاتف المسجل في حسابك غير صالح. تواصل مع الدعم.']);
        }

        return view('laboratory.verify-phone', [
            'phoneE164' => $phoneE164,
            'maskedPhone' => PhoneNormalizer::mask($phoneE164),
            'otpConfigured' => $this->laboratoryAuthService->isOtpDeliveryConfigured(),
            'wasenderReady' => app(OtpSmsSender::class)->isConfigured(),
        ]);
    }

    public function sendPhoneOtp(): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $this->laboratoryAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->laboratoryAuthService->getPostLoginRoute($user));
        }

        try {
            $this->laboratoryAuthService->sendPhoneVerificationOtp($user);
        } catch (RuntimeException|InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return back()->with('success', 'تم إرسال كود التحقق عبر واتساب.');
    }

    public function verifyPhone(VerifyLaboratoryPhoneRequest $request): RedirectResponse
    {
        $user = Auth::guard('web')->user();

        if (! $this->laboratoryAuthService->needsPhoneVerification($user)) {
            return redirect()->route($this->laboratoryAuthService->getPostLoginRoute($user));
        }

        try {
            $this->laboratoryAuthService->verifyPhoneWithOtp($user, $request->code);
        } catch (InvalidArgumentException $e) {
            return back()->withErrors(['code' => $e->getMessage()]);
        }

        return redirect()
            ->route('laboratory.pending')
            ->with('success', 'تم تفعيل رقم هاتفك. حساب المختبر قيد المراجعة من قبل الإدارة.');
    }

    public function logout(): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request = request();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('laboratory.login');
    }
}
