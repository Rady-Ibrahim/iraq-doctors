<?php

namespace Modules\Review\Http\Controllers\Api;

use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Review\Http\Requests\Api\CreateReviewRequest;
use Modules\Review\Services\Api\ReviewService;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService) {}

    public function create(CreateReviewRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user->isPatient()) {
            return $this->forbidden('فقط المرضى يمكنهم إضافة تقييم', 'NOT_PATIENT');
        }

        $data = $request->validated();
        $data['patient_id'] = $user->id;

        $review = $this->reviewService->create($data);

        if (! $review) {
            return $this->forbidden('لا يمكن إضافة التقييم — تأكد من اكتمال الطلب وأنك لم تُقيّمه مسبقاً', 'REVIEW_NOT_ALLOWED');
        }

        return $this->created([
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
        ], 'تم إرسال التقييم وسيُعرض بعد موافقة الإدارة');
    }

    public function doctorReviews(string $doctorId): JsonResponse
    {
        $reviews = $this->reviewService->getDoctorReviews($doctorId);

        return $this->success($reviews->map(fn ($review) => $this->formatPublicReview($review)));
    }

    public function pharmacyReviews(string $pharmacyId): JsonResponse
    {
        $reviews = $this->reviewService->getPharmacyReviews((int) $pharmacyId);

        return $this->success($reviews->map(fn ($review) => $this->formatPublicReview($review)));
    }

    public function laboratoryReviews(string $laboratoryId): JsonResponse
    {
        $reviews = $this->reviewService->getLaboratoryReviews((int) $laboratoryId);

        return $this->success($reviews->map(fn ($review) => $this->formatPublicReview($review)));
    }

    public function myReviews(): JsonResponse
    {
        $user = auth('sanctum')->user();
        $reviews = $this->reviewService->getPatientReviews($user->id);

        return $this->success($reviews->map(function ($review) {
            $target = null;
            if ($review->doctor_id) {
                $target = [
                    'type' => 'doctor',
                    'id' => $review->doctor_id,
                    'name' => $review->doctor?->user?->name,
                    'subtitle' => $review->doctor?->speciality?->name_ar,
                ];
            } elseif ($review->pharmacy_id) {
                $target = [
                    'type' => 'pharmacy',
                    'id' => $review->pharmacy_id,
                    'name' => $review->pharmacy?->name,
                    'subtitle' => 'صيدلية',
                ];
            } elseif ($review->laboratory_id) {
                $target = [
                    'type' => 'laboratory',
                    'id' => $review->laboratory_id,
                    'name' => $review->laboratory?->name,
                    'subtitle' => 'معمل تحاليل',
                ];
            }

            return [
                'id' => $review->id,
                'target' => $target,
                'doctor_name' => $review->doctor?->user?->name,
                'speciality' => $review->doctor?->speciality?->name_ar,
                'pharmacy_name' => $review->pharmacy?->name,
                'laboratory_name' => $review->laboratory?->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'status' => $review->status,
                'created_at' => $review->created_at,
            ];
        }));
    }

    protected function formatPublicReview($review): array
    {
        return [
            'id' => $review->id,
            'patient_name' => $review->patient->name,
            'rating' => $review->rating,
            'comment' => $review->comment,
            'status' => $review->status,
            'created_at' => $review->created_at,
        ];
    }
}
