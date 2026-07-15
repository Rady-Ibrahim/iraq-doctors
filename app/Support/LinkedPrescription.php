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
            'medicines' => $record->prescription ?? [],
            'lab_tests_requested' => $record->lab_tests_requested ?? [],
            'notes' => $record->notes,
            'referral_status' => $record->referral_status,
        ];
    }
}
