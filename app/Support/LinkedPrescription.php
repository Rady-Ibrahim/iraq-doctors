<?php

namespace App\Support;

use Modules\MedicalRecord\Models\MedicalRecord;

class LinkedPrescription
{
    public static function format(?MedicalRecord $record): ?array
    {
        if (! $record) {
            return null;
        }

        $record->loadMissing(['doctor.user']);

        return [
            'id' => $record->id,
            'doctor_name' => $record->doctor?->user?->name,
            'diagnosis' => $record->diagnosis,
            'medicines' => self::normalizeMedicines($record->prescription),
            'lab_tests_requested' => is_array($record->lab_tests_requested)
                ? array_values($record->lab_tests_requested)
                : [],
            'notes' => is_string($record->notes) ? $record->notes : null,
            'referral_status' => $record->referral_status,
        ];
    }

    protected static function normalizeMedicines(mixed $prescription): array
    {
        if (is_string($prescription)) {
            $decoded = json_decode($prescription, true);
            $prescription = is_array($decoded) ? $decoded : [];
        }

        if (! is_array($prescription)) {
            return [];
        }

        return array_values(array_filter($prescription, fn ($row) => is_array($row) || is_string($row)));
    }
}
