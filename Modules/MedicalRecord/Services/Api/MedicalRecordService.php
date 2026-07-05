<?php

namespace Modules\MedicalRecord\Services\Api;

use Modules\MedicalRecord\Models\MedicalRecord;
use Modules\Appointment\Models\Appointment;
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

    public function getPatientRecord(int $patientId, int $recordId): ?MedicalRecord
    {
        return MedicalRecord::with([
            'doctor.user',
            'doctor.speciality',
            'appointment',
            'laboratory',
            'laboratoryOrder.items',
            'pharmacy',
            'pharmacyOrder.items',
        ])
            ->where('patient_id', $patientId)
            ->find($recordId);
    }

    public function getPatientHistory(int $patientId, ?string $recordType = null)
    {
        $query = MedicalRecord::with([
            'doctor.user',
            'doctor.speciality',
            'appointment',
            'laboratory',
            'pharmacy',
        ])
            ->where('patient_id', $patientId)
            ->orderByDesc('created_at');

        if ($recordType) {
            $query->where('record_type', $recordType);
        }

        return $query->get();
    }

    public function getPatientPrescriptions(int $patientId)
    {
        return MedicalRecord::with(['doctor.user', 'doctor.speciality', 'appointment'])
            ->where('patient_id', $patientId)
            ->where('record_type', 'prescription')
            ->orderByDesc('created_at')
            ->get();
    }

    public function getPatientArchive(int $patientId)
    {
        return MedicalRecord::with([
            'doctor.user',
            'doctor.speciality',
            'laboratory',
            'pharmacy',
        ])
            ->where('patient_id', $patientId)
            ->whereIn('record_type', ['prescription', 'lab_result', 'pharmacy_order'])
            ->orderByDesc('created_at')
            ->get();
    }

    public function formatRecordSummary(MedicalRecord $record): array
    {
        $meta = $this->resolveRecordMeta($record);

        return [
            'id' => $record->id,
            'record_type' => $record->record_type,
            'record_type_label' => $meta['type_label'],
            'title' => $meta['title'],
            'provider_name' => $meta['provider_name'],
            'appointment_id' => $record->appointment_id,
            'appointment_date' => $record->appointment?->appointment_date,
            'has_attachments' => ! empty($record->attachments),
            'has_prescription' => ! empty($record->prescription),
            'created_at' => $record->created_at?->format('Y-m-d H:i'),
        ];
    }

    public function formatRecordDetail(MedicalRecord $record): array
    {
        $meta = $this->resolveRecordMeta($record);
        $attachments = collect($record->attachments ?? [])->map(function ($file) {
            $path = $file['file_path'] ?? null;

            return [
                'file_name' => $file['file_name'] ?? null,
                'file_path' => $path && ! str_starts_with($path, 'http')
                    ? storage_public_url(ltrim($path, '/'))
                    : $path,
                'file_type' => $file['file_type'] ?? null,
                'file_size' => $file['file_size'] ?? null,
                'uploaded_at' => $file['uploaded_at'] ?? null,
            ];
        })->values()->all();

        return [
            'id' => $record->id,
            'record_type' => $record->record_type,
            'record_type_label' => $meta['type_label'],
            'title' => $meta['title'],
            'provider_name' => $meta['provider_name'],
            'appointment_id' => $record->appointment_id,
            'appointment_date' => $record->appointment?->appointment_date,
            'doctor_name' => $record->doctor?->user?->name,
            'speciality' => $record->doctor?->speciality?->name_ar,
            'laboratory_id' => $record->laboratory_id,
            'laboratory_name' => $record->laboratory?->name,
            'laboratory_order_id' => $record->laboratory_order_id,
            'pharmacy_id' => $record->pharmacy_id,
            'pharmacy_name' => $record->pharmacy?->name,
            'pharmacy_order_id' => $record->pharmacy_order_id,
            'diagnosis' => $record->diagnosis,
            'prescription' => $record->prescription,
            'notes' => $this->decodeNotes($record->notes),
            'attachments' => $attachments,
            'weight' => $record->weight,
            'height' => $record->height,
            'blood_pressure' => $record->blood_pressure,
            'allergies' => $record->allergies,
            'items' => $meta['items'],
            'created_at' => $record->created_at?->format('Y-m-d H:i'),
        ];
    }

    protected function resolveRecordMeta(MedicalRecord $record): array
    {
        return match ($record->record_type) {
            'prescription' => [
                'type_label' => 'روشتة',
                'title' => 'روشتة طبية',
                'provider_name' => $record->doctor?->user?->name,
                'items' => collect($record->prescription ?? [])->values()->all(),
            ],
            'lab_result' => [
                'type_label' => 'نتيجة تحليل',
                'title' => $record->diagnosis ?? 'نتائج تحاليل',
                'provider_name' => $record->laboratory?->name,
                'items' => $record->laboratoryOrder?->items
                    ?->map(fn ($item) => [
                        'name' => $item->test_name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                    ])->values()->all() ?? [],
            ],
            'pharmacy_order' => [
                'type_label' => 'طلب أدوية',
                'title' => $record->diagnosis ?? 'طلب أدوية',
                'provider_name' => $record->pharmacy?->name,
                'items' => $record->pharmacyOrder?->items
                    ?->map(fn ($item) => [
                        'name' => $item->medicine_name,
                        'price' => $item->price,
                        'quantity' => $item->quantity,
                    ])->values()->all()
                    ?? collect($record->prescription ?? [])->values()->all(),
            ],
            'report' => [
                'type_label' => 'تقرير',
                'title' => $record->diagnosis ?? 'تقرير طبي',
                'provider_name' => $record->doctor?->user?->name,
                'items' => [],
            ],
            default => [
                'type_label' => 'تشخيص',
                'title' => $record->diagnosis ?? 'سجل طبي',
                'provider_name' => $record->doctor?->user?->name,
                'items' => [],
            ],
        };
    }

    protected function decodeNotes(?string $notes): mixed
    {
        if ($notes === null || $notes === '') {
            return null;
        }

        $decoded = json_decode($notes, true);

        return json_last_error() === JSON_ERROR_NONE ? $decoded : $notes;
    }

    public function addAttachment(string $recordId, array $fileData): ?MedicalRecord
    {
        return DB::transaction(function () use ($recordId, $fileData) {
            $record = MedicalRecord::findOrFail($recordId);

            $attachments = $record->attachments ?? [];
            $attachments[] = $fileData;

            $record->update(['attachments' => $attachments]);

            return $record;
        });
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
