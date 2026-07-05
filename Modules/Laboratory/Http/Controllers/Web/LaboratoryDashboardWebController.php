<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Laboratory\Models\Laboratory;

class LaboratoryDashboardWebController extends Controller
{
    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function dashboard(): View
    {
        return view('laboratory.dashboard', [
            'laboratory' => $this->resolveLaboratory(),
        ]);
    }

    public function settings(): View
    {
        return view('laboratory.settings', [
            'laboratory' => $this->resolveLaboratory(),
            'governorates' => \Modules\Doctor\Models\Governorate::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function branches(): View
    {
        return view('laboratory.branches', [
            'laboratory' => $this->resolveLaboratory(),
            'governorates' => \Modules\Doctor\Models\Governorate::where('is_active', true)->orderBy('name_ar')->get(),
        ]);
    }

    public function subscriptionPlans(): View
    {
        return view('laboratory.subscription.plans', [
            'laboratory' => $this->resolveLaboratory(),
        ]);
    }

    public function tests(): View
    {
        return view('laboratory.tests.index', [
            'laboratory' => $this->resolveLaboratory(),
        ]);
    }

    public function orders(): View
    {
        return view('laboratory.orders.index', [
            'laboratory' => $this->resolveLaboratory(),
        ]);
    }

    public function orderShow(int $orderId): View
    {
        return view('laboratory.orders.show', [
            'laboratory' => $this->resolveLaboratory(),
            'orderId' => $orderId,
        ]);
    }

    public function reports(): View
    {
        return view('laboratory.reports.index', [
            'laboratory' => $this->resolveLaboratory(),
        ]);
    }
}
