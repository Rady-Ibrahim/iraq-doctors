<?php

namespace Modules\Laboratory\Http\Controllers\Web;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Modules\Laboratory\Http\Requests\Web\ResubmitLaboratoryDocumentsRequest;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Services\Web\LaboratoryAuthService;

class LaboratoryVerificationController extends Controller
{
    public function __construct(private LaboratoryAuthService $laboratoryAuthService) {}

    protected function resolveLaboratory(): Laboratory
    {
        return Laboratory::where('user_id', auth('web')->id())->firstOrFail();
    }

    public function pending(): View|RedirectResponse
    {
        $laboratory = $this->resolveLaboratory();

        if ($laboratory->status === 'approved') {
            return redirect()->route('laboratory.dashboard');
        }

        if ($laboratory->status === 'rejected') {
            return redirect()->route('laboratory.rejected');
        }

        if ($laboratory->status === 'suspended') {
            return redirect()->route('laboratory.suspended');
        }

        return view('laboratory.pending', [
            'laboratory' => $laboratory->load('governorate'),
        ]);
    }

    public function rejected(): View|RedirectResponse
    {
        $laboratory = $this->resolveLaboratory();

        if ($laboratory->status === 'approved') {
            return redirect()->route('laboratory.dashboard');
        }

        if ($laboratory->status === 'pending') {
            return redirect()->route('laboratory.pending');
        }

        return view('laboratory.rejected', compact('laboratory'));
    }

    public function suspended(): View|RedirectResponse
    {
        $laboratory = $this->resolveLaboratory();

        if ($laboratory->status === 'approved') {
            return redirect()->route('laboratory.dashboard');
        }

        return view('laboratory.suspended', compact('laboratory'));
    }

    public function resubmit(ResubmitLaboratoryDocumentsRequest $request): RedirectResponse
    {
        $this->laboratoryAuthService->resubmitDocuments(
            auth('web')->id(),
            $request->file('commercial_register_document'),
            $request->file('license_document'),
            $request->file('owner_id_document'),
            $request->file('logo'),
            $request->file('accreditation_document')
        );

        return redirect()
            ->route('laboratory.pending')
            ->with('success', 'تم إرسال مستنداتك مجدداً. حساب المختبر قيد المراجعة.');
    }
}
