<?php

namespace Modules\MedicalRecord\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;
use Modules\MedicalRecord\Http\Requests\Api\CreateMedicalRecordRequest;
use Modules\MedicalRecord\Http\Requests\Api\UploadAttachmentRequest;
use Modules\MedicalRecord\Services\Api\MedicalRecordService;
use App\Traits\ApiResponse;

class MedicalRecordController extends Controller
{
    use ApiResponse;

    public function __construct(private MedicalRecordService $medicalRecordService) {}

    protected function ensurePatient(): ?JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user?->isPatient()) {
            return $this->forbidden('فقط المرضى يمكنهم عرض سجلهم الطبي', 'NOT_PATIENT');
        }

        return null;
    }

    public function store(CreateMedicalRecordRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم إضافة سجلات طبية', 'NOT_DOCTOR');
        }

        $appointment = \Modules\Appointment\Models\Appointment::findOrFail($request->appointment_id);

        if ($appointment->doctor->user_id !== $user->id) {
            return $this->forbidden('غير مصرح، هذا الموعد ليس لك');
        }

        $data = $request->validated();
        $data['created_by'] = $user->id;

        $record = $this->medicalRecordService->create($data);

        if (! $record) {
            return $this->error('لا يمكن إضافة سجل طبي، الموعد غير مكتمل أو السجل موجود بالفعل', 'RECORD_NOT_ALLOWED', 400);
        }

        return $this->created(
            $this->medicalRecordService->formatRecordDetail($record),
            'تم إضافة السجل الطبي بنجاح'
        );
    }

    public function show(string $appointmentId): JsonResponse
    {
        $user = auth('sanctum')->user();
        $record = $this->medicalRecordService->getByAppointment($appointmentId);

        if (! $record) {
            return $this->notFound('السجل الطبي غير موجود', 'RECORD_NOT_FOUND');
        }

        if ($user->isPatient() && $record->patient_id !== $user->id) {
            return $this->forbidden('غير مصرح');
        }

        if ($user->isDoctor()) {
            $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
            if (! $doctor || $record->doctor_id !== $doctor->id) {
                return $this->forbidden('غير مصرح');
            }
        }

        return $this->success($this->medicalRecordService->formatRecordDetail($record));
    }

    public function showRecord(string $id): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $record = $this->medicalRecordService->getPatientRecord(auth('sanctum')->id(), (int) $id);

        if (! $record) {
            return $this->notFound('السجل غير موجود');
        }

        return $this->success($this->medicalRecordService->formatRecordDetail($record));
    }

    public function patientHistory(): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $recordType = request('record_type');
        $records = $this->medicalRecordService->getPatientHistory(auth('sanctum')->id(), $recordType);

        return $this->success(
            $records->map(fn ($record) => $this->medicalRecordService->formatRecordSummary($record))->values()->all()
        );
    }

    public function patientPrescriptions(): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $records = $this->medicalRecordService->getPatientPrescriptions(auth('sanctum')->id());

        return $this->success(
            $records->map(fn ($record) => $this->medicalRecordService->formatRecordDetail($record))->values()->all()
        );
    }

    public function patientArchive(): JsonResponse
    {
        if ($response = $this->ensurePatient()) {
            return $response;
        }

        $records = $this->medicalRecordService->getPatientArchive(auth('sanctum')->id());

        return $this->success(
            $records->map(fn ($record) => $this->medicalRecordService->formatRecordSummary($record))->values()->all()
        );
    }

    public function uploadAttachment(string $recordId, UploadAttachmentRequest $request): JsonResponse
    {
        $user = auth('sanctum')->user();

        if (! $user->isDoctor()) {
            return $this->forbidden('فقط الأطباء يمكنهم رفع ملفات', 'NOT_DOCTOR');
        }

        $record = \Modules\MedicalRecord\Models\MedicalRecord::findOrFail($recordId);

        $doctor = \Modules\Doctor\Models\Doctor::where('user_id', $user->id)->first();
        if (! $doctor || $record->doctor_id !== $doctor->id) {
            return $this->forbidden('غير مصرح');
        }

        $fileData = $this->medicalRecordService->uploadFile($request->file('file'));
        $this->medicalRecordService->addAttachment($recordId, $fileData);

        return $this->created([
            'file_name' => $fileData['file_name'],
            'file_path' => $fileData['file_path'],
            'file_type' => $fileData['file_type'],
        ], 'تم رفع الملف بنجاح');
    }
}
