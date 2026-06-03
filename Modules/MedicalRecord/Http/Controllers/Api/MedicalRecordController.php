<?php

namespace Modules\MedicalRecord\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\MedicalRecord\Http\Requests\Api\CreateMedicalRecordRequest;
use Modules\MedicalRecord\Http\Requests\Api\UploadAttachmentRequest;
use Modules\MedicalRecord\Services\Api\MedicalRecordService;

class MedicalRecordController extends Controller
{
    public function __construct(private MedicalRecordService $medicalRecordService)
    {
    }

    public function store(CreateMedicalRecordRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم إضافة سجلات طبية',
                ],
            ], 403);
        }

        $appointment = \Modules\Appointment\Models\Appointment::findOrFail($request->appointment_id);

        if ($appointment->doctor->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح، هذا الموعد ليس لك',
                ],
            ], 403);
        }

        $data = $request->validated();
        $data['created_by'] = $user->id;

        $record = $this->medicalRecordService->create($data);

        if (!$record) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RECORD_NOT_ALLOWED',
                    'message' => 'لا يمكن إضافة سجل طبي، الموعد غير مكتمل أو السجل موجود بالفعل',
                ],
            ], 400);
        }

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $record->id,
                'appointment_id' => $record->appointment_id,
                'record_type' => $record->record_type,
                'diagnosis' => $record->diagnosis,
                'prescription' => $record->prescription,
                'notes' => $record->notes,
            ],
            'message' => 'تم إضافة السجل الطبي بنجاح',
        ], 201);
    }

    public function show(string $appointmentId): JsonResponse
    {
        $user = auth('sanctum')->user();
        $record = $this->medicalRecordService->getByAppointment($appointmentId);

        if (!$record) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'RECORD_NOT_FOUND',
                    'message' => 'السجل الطبي غير موجود',
                ],
            ], 404);
        }

        if ($user->isPatient() && $record->patient_id !== $user->id) {
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
            if (!$doctor || $record->doctor_id !== $doctor->id) {
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
                'id' => $record->id,
                'appointment_id' => $record->appointment_id,
                'doctor_name' => $record->doctor->user->name,
                'speciality' => $record->doctor->speciality->name_ar,
                'record_type' => $record->record_type,
                'diagnosis' => $record->diagnosis,
                'prescription' => $record->prescription,
                'notes' => $record->notes,
                'attachments' => $record->attachments,
                'created_at' => $record->created_at,
            ],
        ], 200);
    }

    public function patientHistory(): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isPatient()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_PATIENT',
                    'message' => 'فقط المرضى يمكنهم عرض سجلهم الطبي',
                ],
            ], 403);
        }

        $recordType = request('record_type');
        $records = $this->medicalRecordService->getPatientHistory($user->id, $recordType);

        return response()->json([
            'success' => true,
            'data' => $records->map(function ($record) {
                return [
                    'id' => $record->id,
                    'appointment_id' => $record->appointment_id,
                    'appointment_date' => $record->appointment->appointment_date,
                    'doctor_name' => $record->doctor->user->name,
                    'speciality' => $record->doctor->speciality->name_ar,
                    'record_type' => $record->record_type,
                    'diagnosis' => $record->diagnosis,
                    'has_attachments' => !empty($record->attachments),
                    'created_at' => $record->created_at,
                ];
            }),
        ], 200);
    }

    public function uploadAttachment(string $recordId, UploadAttachmentRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (!$user->isDoctor()) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'NOT_DOCTOR',
                    'message' => 'فقط الأطباء يمكنهم رفع ملفات',
                ],
            ], 403);
        }

        $record = \Modules\MedicalRecord\Models\MedicalRecord::findOrFail($recordId);

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (!$doctor || $record->doctor_id !== $doctor->id) {
            return response()->json([
                'success' => false,
                'error' => [
                    'code' => 'UNAUTHORIZED',
                    'message' => 'غير مصرح',
                ],
            ], 403);
        }

        $fileData = $this->medicalRecordService->uploadFile($request->file('file'));
        $updatedRecord = $this->medicalRecordService->addAttachment($recordId, $fileData);

        return response()->json([
            'success' => true,
            'data' => [
                'file_name' => $fileData['file_name'],
                'file_path' => $fileData['file_path'],
                'file_type' => $fileData['file_type'],
            ],
            'message' => 'تم رفع الملف بنجاح',
        ], 201);
    }
}
