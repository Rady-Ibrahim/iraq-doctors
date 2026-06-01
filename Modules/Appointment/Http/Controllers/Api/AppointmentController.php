<?php

namespace Modules\Appointment\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Appointment\Http\Requests\Api\BookAppointmentRequest;
use Modules\Appointment\Services\Api\AppointmentService;

class AppointmentController extends Controller
{
    public function __construct(private AppointmentService $appointmentService)
    {
    }

    public function book(BookAppointmentRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isPatient()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_PATIENT',
                    'message' => 'فقط المرضى يمكنهم حجز المواعيد',
                ],
            ], 403);
        }

        $data = $request->validated();
        $data['patient_id'] = $user->id;

        if (!$this->appointmentService->checkAvailability($data['doctor_id'], $data['appointment_date'], $data['appointment_time'])) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'SLOT_NOT_AVAILABLE',
                    'message' => 'الموعد غير متاح',
                ],
            ], 409);
        }

        try {
            $appointment = $this->appointmentService->book($data);

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $appointment->id,
                    'doctor_id' => $appointment->doctor_id,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                    'price' => $appointment->price,
                ],
                'message' => 'تم حجز الموعد بنجاح',
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'BOOKING_FAILED',
                    'message' => 'فشل حجز الموعد',
                ],
            ], 500);
        }
    }

    public function myAppointments(): JsonResponse
    {
        $user = auth('sanctum')->user();
        $status = request('status');

        if ($user->isPatient()) {
            $appointments = $this->appointmentService->getPatientAppointments($user->id, $status);
        } elseif ($user->isDoctor()) {
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
            if (!$doctor) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'DOCTOR_PROFILE_NOT_FOUND',
                        'message' => 'ملف الطبيب غير موجود',
                    ],
                ], 404);
            }
            $appointments = $this->appointmentService->getDoctorAppointments($doctor->id, $status);
        } else {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح',
                ],
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $appointments->map(function ($appointment) {
                return [
                    'id' => $appointment->id,
                    'doctor_name' => $appointment->doctor->user->name,
                    'speciality' => $appointment->doctor->speciality->name_ar,
                    'patient_name' => $appointment->patient->name,
                    'appointment_date' => $appointment->appointment_date,
                    'appointment_time' => $appointment->appointment_time,
                    'status' => $appointment->status,
                    'price' => $appointment->price,
                ];
            }),
        ], 200);
    }

    public function show(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();
        $appointment = \Modules\Appointment\Models\Appointment::with(['doctor.user', 'doctor.speciality', 'patient'])
            ->findOrFail($id);

        if ($user->isPatient() && $appointment->patient_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح',
                ],
            ], 403);
        }

        if ($user->isDoctor()) {
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $appointment->doctor_id !== $doctor->id) {
                return response()->json([
                    'success' => false,
                    'error' => [
                        'code' => 'UNAUTHORIZED',
                        'message' => 'غير مصرح',
                    ],
                ], 403);
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $appointment->id,
                'doctor' => [
                    'name' => $appointment->doctor->user->name,
                    'speciality' => $appointment->doctor->speciality->name_ar,
                ],
                'patient' => [
                    'name' => $appointment->patient->name,
                    'phone' => $appointment->patient->phone,
                ],
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'price' => $appointment->price,
                'payment_status' => $appointment->payment_status,
                'notes' => $appointment->notes,
            ],
        ], 200);
    }

    public function cancel(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();
        $appointment = $this->appointmentService->cancel($id, $user->id);

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CANCELLATION_FAILED',
                    'message' => 'فشل إلغاء الموعد',
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء الموعد بنجاح',
        ], 200);
    }

    public function confirm(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم تأكيد المواعيد',
                ],
            ], 403);
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DOCTOR_PROFILE_NOT_FOUND',
                    'message' => 'ملف الطبيب غير موجود',
                ],
            ], 404);
        }

        $appointment = \Modules\Appointment\Models\Appointment::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'APPOINTMENT_NOT_FOUND',
                    'message' => 'الموعد غير موجود',
                ],
            ], 404);
        }

        $result = $this->appointmentService->confirm($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'CONFIRMATION_FAILED',
                    'message' => 'فشل تأكيد الموعد',
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم تأكيد الموعد بنجاح',
        ], 200);
    }

    public function complete(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم إكمال المواعيد',
                ],
            ], 403);
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'DOCTOR_PROFILE_NOT_FOUND',
                    'message' => 'ملف الطبيب غير موجود',
                ],
            ], 404);
        }

        $appointment = \Modules\Appointment\Models\Appointment::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (!$appointment) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'APPOINTMENT_NOT_FOUND',
                    'message' => 'الموعد غير موجود',
                ],
            ], 404);
        }

        $result = $this->appointmentService->complete($id);

        if (!$result) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'COMPLETION_FAILED',
                    'message' => 'فشل إكمال الموعد',
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'message' => 'تم إكمال الموعد بنجاح',
        ], 200);
    }
}
