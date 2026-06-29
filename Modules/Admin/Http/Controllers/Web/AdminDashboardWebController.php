<?php

namespace Modules\Admin\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\View\View;

class AdminDashboardWebController extends Controller
{
    public function dashboard(): View
    {
        return view('admin.dashboard');
    }

    public function doctors(): View
    {
        return view('admin.doctors.index');
    }

    public function doctorShow(int $id): View
    {
        return view('admin.doctors.show', ['doctorId' => $id]);
    }

    public function patients(): View
    {
        return view('admin.patients.index');
    }

    public function patientShow(int $id): View
    {
        return view('admin.patients.show', ['patientId' => $id]);
    }

    public function appointments(): View
    {
        return view('admin.appointments.index');
    }

    public function revenue(): View
    {
        return view('admin.revenue');
    }

    public function subscriptions(): View
    {
        return view('admin.subscriptions.index');
    }

    public function reviews(): View
    {
        return view('admin.reviews.index');
    }

    public function analytics(): View
    {
        return view('admin.analytics');
    }

    public function specialities(): View
    {
        return view('admin.specialities.index');
    }

    public function governorates(): View
    {
        return view('admin.governorates.index');
    }

    public function users(): View
    {
        return view('admin.users.index');
    }
}
