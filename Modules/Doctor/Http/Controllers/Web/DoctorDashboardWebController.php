<?php

namespace Modules\Doctor\Http\Controllers\Web;

use Illuminate\Routing\Controller;
use Illuminate\View\View;
use App\Traits\ResolvesDoctorDashboard;

class DoctorDashboardWebController extends Controller
{
    use ResolvesDoctorDashboard;

    public function dashboard(): View
    {
        return view('doctor.dashboard', ['doctor' => $this->resolveDoctor()]);
    }

    public function calendar(): View
    {
        return view('doctor.calendar');
    }

    public function settings(): View
    {
        return view('doctor.settings', ['doctor' => $this->resolveDoctor()]);
    }

    public function support(): View
    {
        return view('doctor.support');
    }

    public function patients(): View
    {
        return view('doctor.patients.index');
    }

    public function patientShow(int $id): View
    {
        return view('doctor.patients.show', ['patientId' => $id]);
    }

    public function patientRecords(int $id): View
    {
        return view('doctor.patients.records', ['patientId' => $id]);
    }

    public function prescriptions(): View
    {
        return view('doctor.prescriptions.index');
    }

    public function prescriptionCreate(): View
    {
        return view('doctor.prescriptions.create');
    }

    public function prescriptionShow(int $id): View
    {
        return view('doctor.prescriptions.show', ['prescriptionId' => $id]);
    }

    public function prescriptionEdit(int $id): View
    {
        return view('doctor.prescriptions.edit', ['prescriptionId' => $id]);
    }

    public function records(): View
    {
        return view('doctor.records.index');
    }

    public function recordCreate(): View
    {
        return view('doctor.records.create');
    }

    public function recordShow(int $id): View
    {
        return view('doctor.records.show', ['recordId' => $id]);
    }

    public function recordEdit(int $id): View
    {
        return view('doctor.records.edit', ['recordId' => $id]);
    }

    public function subscriptionPlans(): View
    {
        return view('doctor.subscription.plans', ['doctor' => $this->resolveDoctor()]);
    }

    public function requests(): View
    {
        return view('doctor.requests.index');
    }

    public function staff(): View
    {
        return view('doctor.staff.index', ['doctor' => $this->resolveDoctor()]);
    }
}
