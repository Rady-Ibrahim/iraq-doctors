<?php

namespace Modules\Doctor\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Doctor\Http\Requests\Web\ResubmitDoctorDocumentsRequest;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Services\Web\DoctorAuthService;

class DoctorVerificationController extends Controller
{
    public function __construct(private DoctorAuthService $doctorAuthService) {}

    protected function resolveDoctor(): Doctor
    {
        return Doctor::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function pending(): View|RedirectResponse
    {
        $doctor = $this->resolveDoctor();

        if ($doctor->status === 'approved') {
            return redirect()->route('doctor.dashboard');
        }

        if ($doctor->status === 'rejected') {
            return redirect()->route('doctor.rejected');
        }

        return view('doctor.pending', ['doctor' => $doctor->load('speciality')]);
    }

    public function rejected(): View|RedirectResponse
    {
        $doctor = $this->resolveDoctor();

        if ($doctor->status === 'approved') {
            return redirect()->route('doctor.dashboard');
        }

        if ($doctor->status === 'pending') {
            return redirect()->route('doctor.pending');
        }

        return view('doctor.rejected', compact('doctor'));
    }

    public function suspended(): View|RedirectResponse
    {
        $doctor = $this->resolveDoctor();

        if ($doctor->status === 'approved') {
            return redirect()->route('doctor.dashboard');
        }

        return view('doctor.suspended', compact('doctor'));
    }

    public function resubmit(ResubmitDoctorDocumentsRequest $request): RedirectResponse
    {
        $this->doctorAuthService->resubmitDocuments(
            auth('web')->id(),
            $request->file('license_document'),
            $request->file('clinic_image')
        );

        return redirect()
            ->route('doctor.pending')
            ->with('success', 'تم إرسال مستنداتك مجدداً. حسابك قيد المراجعة.');
    }
}
