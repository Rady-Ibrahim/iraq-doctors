<?php

namespace Modules\MedicalRecord\Services\Api;

use Modules\MedicalRecord\Models\MedicalRecord;
use Modules\Appointment\Models\Appointment;
use Modules\Doctor\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class MedicalRecordService
{
    public function create(array $data): ?MedicalRecord
    {
        return DB::transaction(function () use ($data) {
            $appointment = Appointment::findOrFail($data['appointment_id']);

            if ($appointment->status !== 'completed') {
                return null;
            }

            if (MedicalRecord::where('appointment_id', $data['appointment_id'])->exists()) {
                return null;
            }

            $record = MedicalRecord::create([
                'appointment_id' => $data['appointment_id'],
                'doctor_id' => $appointment->doctor_id,
                'patient_id' => $appointment->patient_id,
                'record_type' => $data['record_type'] ?? 'diagnosis',
                'diagnosis' => $data['diagnosis'] ?? null,
                'prescription' => $data['prescription'] ?? null,
                'notes' => $data['notes'] ?? null,
                'attachments' => $data['attachments'] ?? [],
                'created_by' => $data['created_by'],
                'weight' => $data['weight'] ?? null,
                'height' => $data['height'] ?? null,
                'blood_pressure' => $data['blood_pressure'] ?? null,
                'allergies' => $data['allergies'] ?? null,
            ]);

            return $record;
        });
    }

    public function getByAppointment(string $appointmentId): ?MedicalRecord
    {
        return MedicalRecord::with(['doctor.user', 'doctor.speciality', 'patient'])
            ->where('appointment_id', $appointmentId)
            ->first();
    }

    public function getPatientHistory(string $patientId, ?string $recordType = null)
    {
        $query = MedicalRecord::with(['doctor.user', 'doctor.speciality', 'appointment'])
            ->where('patient_id', $patientId)
            ->orderBy('created_at', 'desc');

        if ($recordType) {
            $query->where('record_type', $recordType);
        }

        return $query->get();
    }

    public function addAttachment(string $recordId, array $fileData): ?MedicalRecord
    {
        $record = MedicalRecord::findOrFail($recordId);

        $attachments = $record->attachments ?? [];
        $attachments[] = $fileData;

        $record->update(['attachments' => $attachments]);

        return $record;
    }

    public function uploadFile($file): array
    {
        $path = $file->store('medical-records', 'public');
        
        return [
            'file_name' => $file->getClientOriginalName(),
            'file_path' => Storage::url($path),
            'file_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'uploaded_at' => now()->toDateTimeString(),
        ];
    }
}
