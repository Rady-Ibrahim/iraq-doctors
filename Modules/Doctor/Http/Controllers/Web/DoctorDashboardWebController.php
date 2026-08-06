<?php

namespace Modules\Doctor\Http\Controllers\Web;

use App\Support\PdfReport;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use App\Traits\ResolvesDoctorDashboard;
use Modules\Doctor\Services\DoctorDashboardService;

class DoctorDashboardWebController extends Controller
{
    use ResolvesDoctorDashboard;

    public function __construct(private DoctorDashboardService $doctorDashboardService) {}

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

    public function prescriptionPdf(int $id): Response
    {
        $doctor = $this->resolveDoctor();
        $prescription = $this->doctorDashboardService->getPrescription($doctor->id, $id);

        return PdfReport::download('pdf.doctor.prescription', [
            'doctor' => $doctor,
            'prescription' => $prescription,
        ], 'prescription-'.$id.'.pdf');
    }

    public function prescriptionPrint(int $id): View
    {
        $doctor = $this->resolveDoctor();
        $prescription = $this->doctorDashboardService->getPrescription($doctor->id, $id);

        return view('pdf.doctor.prescription', [
            'doctor' => $doctor,
            'prescription' => $prescription,
            'print' => true,
        ]);
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

    public function recordPdf(int $id): Response
    {
        $doctor = $this->resolveDoctor();
        $record = $this->doctorDashboardService->getRecord($doctor->id, $id);

        return PdfReport::download('pdf.doctor.record', [
            'doctor' => $doctor,
            'record' => $record,
            'typeLabel' => $this->recordTypeLabel($record['type'] ?? ''),
        ], 'record-'.$id.'.pdf');
    }

    public function recordPrint(int $id): View
    {
        $doctor = $this->resolveDoctor();
        $record = $this->doctorDashboardService->getRecord($doctor->id, $id);

        return view('pdf.doctor.record', [
            'doctor' => $doctor,
            'record' => $record,
            'typeLabel' => $this->recordTypeLabel($record['type'] ?? ''),
            'print' => true,
        ]);
    }

    protected function recordTypeLabel(?string $type): string
    {
        return match ($type) {
            'diagnosis' => 'تشخيص',
            'treatment' => 'علاج',
            'lab_test' => 'اختبار مختبري',
            'imaging' => 'تصوير',
            'surgery' => 'جراحة',
            'consultation' => 'استشارة',
            'report' => 'تقرير',
            'prescription' => 'وصفة طبية',
            default => $type ?: 'سجل طبي',
        };
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
