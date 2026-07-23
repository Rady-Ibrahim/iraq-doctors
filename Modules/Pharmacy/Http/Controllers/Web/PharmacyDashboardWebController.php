<?php

namespace Modules\Pharmacy\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Doctor\Models\Governorate;
use Modules\Pharmacy\Models\Pharmacy;

class PharmacyDashboardWebController extends Controller
{
    protected function resolvePharmacy(): Pharmacy
    {
        return Pharmacy::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function dashboard(): View
    {
        return view('pharmacy.dashboard', [
            'pharmacy' => $this->resolvePharmacy(),
        ]);
    }

    public function settings(): View
    {
        return view('pharmacy.settings', [
            'pharmacy' => $this->resolvePharmacy(),
            'governorates' => Governorate::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function branches(): View
    {
        return view('pharmacy.branches', [
            'pharmacy' => $this->resolvePharmacy(),
            'governorates' => Governorate::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }


    public function subscriptionPlans(): View
    {
        return view('pharmacy.subscription.plans', [
            'pharmacy' => $this->resolvePharmacy(),
        ]);
    }

    public function medicines(): View
    {
        return view('pharmacy.medicines.index', [
            'pharmacy' => $this->resolvePharmacy(),
        ]);
    }

    public function orders(): View
    {
        return view('pharmacy.orders.index', [
            'pharmacy' => $this->resolvePharmacy(),
        ]);
    }

    public function orderShow(int $orderId): View
    {
        return view('pharmacy.orders.show', [
            'pharmacy' => $this->resolvePharmacy(),
            'orderId' => $orderId,
        ]);
    }

    public function reports(): View
    {
        return view('pharmacy.reports.index', [
            'pharmacy' => $this->resolvePharmacy(),
        ]);
    }
}
