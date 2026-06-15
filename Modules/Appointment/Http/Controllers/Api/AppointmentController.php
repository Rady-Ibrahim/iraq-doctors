<?php

namespace Modules\Appointment\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\Appointment\Http\Requests\Api\BookAppointmentRequest;
use Modules\Appointment\Http\Requests\Api\RescheduleAppointmentRequest;
use Modules\Appointment\Services\Api\AppointmentService;
use App\Traits\ApiResponse;

class AppointmentController extends Controller
{
    use ApiResponse;

    public function __construct(private AppointmentService $appointmentService)
    {
    }

    public function book(BookAppointmentRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isPatient()) {
            return $this->forbidden('فقط المرضى يمكنهم حجز المواعيد', 'NOT_PATIENT');
        }

        $data = $request->validated();
        $data['patient_id'] = $user->id;

        if (!$this->appointmentService->checkAvailability($data['doctor_id'], $data['appointment_date'], $data['appointment_time'])) {
            return $this->error('الموعد غير متاح', 'SLOT_NOT_AVAILABLE', 409);
        }

        try {
            $appointment = $this->appointmentService->book($data);

            return $this->created([
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'appointment_date' => $appointment->appointment_date,
                'appointment_time' => $appointment->appointment_time,
                'status' => $appointment->status,
                'price' => $appointment->price,
            ], 'تم حجز الموعد بنجاح');
        } catch (\Exception $e) {
            return $this->serverError('فشل حجز الموعد');
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
                return $this->notFound('ملف الطبيب غير موجود', 'DOCTOR_PROFILE_NOT_FOUND');
            }
            $appointments = $this->appointmentService->getDoctorAppointments($doctor->id, $status);
        } else {
            return $this->forbidden('غير مصرح');
        }

        return $this->success($appointments->map(function ($appointment) {
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
        }));
    }

    public function show(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();
        $appointment = \Modules\Appointment\Models\Appointment::with(['doctor.user', 'doctor.speciality', 'patient'])
            ->findOrFail($id);

        if ($user->isPatient() && $appointment->patient_id !== $user->id) {
            return $this->forbidden('غير مصرح');
        }

        if ($user->isDoctor()) {
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
            if (!$doctor || $appointment->doctor_id !== $doctor->id) {
                return $this->forbidden('غير مصرح');
            }
        }

        return $this->success([
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
        ]);
    }

    public function cancel(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();
        $appointment = $this->appointmentService->cancel($id, $user->id);

        if (!$appointment) {
            return $this->error('فشل إلغاء الموعد', 'CANCELLATION_FAILED', 400);
        }

        return $this->success(null, 'تم إلغاء الموعد بنجاح');
    }

    public function confirm(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم تأكيد المواعيد', 'NOT_DOCTOR');
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            return $this->notFound('ملف الطبيب غير موجود', 'DOCTOR_PROFILE_NOT_FOUND');
        }

        $appointment = \Modules\Appointment\Models\Appointment::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (!$appointment) {
            return $this->notFound('الموعد غير موجود', 'APPOINTMENT_NOT_FOUND');
        }

        $result = $this->appointmentService->confirm($id);

        if (!$result) {
            return $this->error('فشل تأكيد الموعد', 'CONFIRMATION_FAILED', 400);
        }

        return $this->success(null, 'تم تأكيد الموعد بنجاح');
    }

    public function complete(string $id): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم إكمال المواعيد', 'NOT_DOCTOR');
        }

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor) {
            return $this->notFound('ملف الطبيب غير موجود', 'DOCTOR_PROFILE_NOT_FOUND');
        }

        $appointment = \Modules\Appointment\Models\Appointment::where('id', $id)
            ->where('doctor_id', $doctor->id)
            ->first();

        if (!$appointment) {
            return $this->notFound('الموعد غير موجود', 'APPOINTMENT_NOT_FOUND');
        }

        $result = $this->appointmentService->complete($id);

        if (!$result) {
            return $this->error('فشل إكمال الموعد', 'COMPLETION_FAILED', 400);
        }

        return $this->success(null, 'تم إكمال الموعد بنجاح');
    }

    public function reschedule(string $id, RescheduleAppointmentRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isPatient()) {
            return $this->forbidden('فقط المرضى يمكنهم تعديل المواعيد', 'NOT_PATIENT');
        }

        $appointment = $this->appointmentService->reschedule($id, $user->id, $request->validated());

        if (!$appointment) {
            return $this->error(
                'فشل تعديل الموعد. تأكد أن الموعد قيد الانتظار والوقت الجديد متاح',
                'RESCHEDULE_FAILED',
                400
            );
        }

        return $this->success([
            'id'               => $appointment->id,
            'appointment_date' => $appointment->appointment_date,
            'appointment_time' => $appointment->appointment_time,
            'status'           => $appointment->status,
        ], 'تم تعديل الموعد بنجاح');
    }
}
