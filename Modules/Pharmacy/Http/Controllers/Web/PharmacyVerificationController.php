<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Pharmacy\Http\Requests\Web\ResubmitPharmacyDocumentsRequest;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Services\Web\PharmacyAuthService;

class PharmacyVerificationController extends Controller
{
    public function __construct(private PharmacyAuthService $pharmacyAuthService) {}

    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function pending(): View|RedirectResponse
    {
        $pharmacy = $this->resolvePharmacy();

        if ($pharmacy->status === 'approved') {
            return redirect()->route('pharmacy.dashboard');
        }

        if ($pharmacy->status === 'rejected') {
            return redirect()->route('pharmacy.rejected');
        }

        if ($pharmacy->status === 'suspended') {
            return redirect()->route('pharmacy.suspended');
        }

        return view('pharmacy.pending', [
            'pharmacy' => $pharmacy->load('governorate'),
        ]);
    }

    public function rejected(): View|RedirectResponse
    {
        $pharmacy = $this->resolvePharmacy();

        if ($pharmacy->status === 'approved') {
            return redirect()->route('pharmacy.dashboard');
        }

        if ($pharmacy->status === 'pending') {
            return redirect()->route('pharmacy.pending');
        }

        return view('pharmacy.rejected', compact('pharmacy'));
    }

    public function suspended(): View|RedirectResponse
    {
        $pharmacy = $this->resolvePharmacy();

        if ($pharmacy->status === 'approved') {
            return redirect()->route('pharmacy.dashboard');
        }

        return view('pharmacy.suspended', compact('pharmacy'));
    }

    public function resubmit(ResubmitPharmacyDocumentsRequest $request): RedirectResponse
    {
        $this->pharmacyAuthService->resubmitDocuments(
            auth('web')->id(),
            $request->file('commercial_register_document'),
            $request->file('license_document'),
            $request->file('owner_id_document'),
            $request->file('logo')
        );

        return redirect()
            ->route('pharmacy.pending')
            ->with('success', 'تم إرسال مستنداتك مجدداً. حساب الصيدلية قيد المراجعة.');
    }
}
