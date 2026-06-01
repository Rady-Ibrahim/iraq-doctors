<?php

namespace Modules\Review\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Review\Http\Requests\Api\CreateReviewRequest;
use Modules\Review\Services\Api\ReviewService;

class ReviewController extends Controller
{
    public function __construct(private ReviewService $reviewService)
    {
    }

    public function create(CreateReviewRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isPatient()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_PATIENT',
                    'message' => 'فقط المرضى يمكنهم إضافة تقييم',
                ],
            ], 403);
        }

        $data = $request->validated();
        $data['patient_id'] = $user->id;

        $review = $this->reviewService->create($data);

        if (!$review) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'REVIEW_NOT_ALLOWED',
                    'message' => 'يمكنك التقييم فقط بعد اكتمال الموعد',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ],
            'message' => 'تم إضافة التقييم بنجاح',
        ], 201);
    }

    public function doctorReviews(string $doctorId): JsonResponse
    {
        $reviews = $this->reviewService->getDoctorReviews($doctorId);

        return response()->json([
            'success' => true,
            'data' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'patient_name' => $review->patient->name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                ];
            }),
        ], 200);
    }

    public function myReviews(): JsonResponse
    {
        $user = auth('sanctum')->user();
        $reviews = $this->reviewService->getPatientReviews($user->id);

        return response()->json([
            'success' => true,
            'data' => $reviews->map(function ($review) {
                return [
                    'id' => $review->id,
                    'doctor_name' => $review->doctor->user->name,
                    'speciality' => $review->doctor->speciality->name_ar,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                ];
            }),
        ], 200);
    }
}
