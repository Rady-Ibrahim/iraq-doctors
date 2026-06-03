<?php

namespace Modules\Review\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Review\Http\Requests\Api\CreateReviewRequest;
use Modules\Review\Services\Api\ReviewService;
use App\Traits\ApiResponse;

class ReviewController extends Controller
{
    use ApiResponse;

    public function __construct(private ReviewService $reviewService)
    {
    }

    public function create(CreateReviewRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isPatient()) {
            return $this->forbidden('فقط المرضى يمكنهم إضافة تقييم', 'NOT_PATIENT');
        }

        $data = $request->validated();
        $data['patient_id'] = $user->id;

        $review = $this->reviewService->create($data);

        if (!$review) {
            return $this->forbidden('يمكنك التقييم فقط بعد اكتمال الموعد', 'REVIEW_NOT_ALLOWED');
        }

        return $this->created([
            'id' => $review->id,
            'rating' => $review->rating,
            'comment' => $review->comment,
        ], 'تم إضافة التقييم بنجاح');
    }

    public function doctorReviews(string $doctorId): JsonResponse
    {
        $reviews = $this->reviewService->getDoctorReviews($doctorId);

        return $this->success($reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'patient_name' => $review->patient->name,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            ];
        }));
    }

    public function myReviews(): JsonResponse
    {
        $user = auth('sanctum')->user();
        $reviews = $this->reviewService->getPatientReviews($user->id);

        return $this->success($reviews->map(function ($review) {
            return [
                'id' => $review->id,
                'doctor_name' => $review->doctor->user->name,
                'speciality' => $review->doctor->speciality->name_ar,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
            ];
        }));
    }
}
