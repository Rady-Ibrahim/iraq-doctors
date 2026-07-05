<?php

namespace Modules\Laboratory\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Laboratory\Enums\LaboratoryOrderStatus;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratoryOrder;
use Modules\Laboratory\Models\LaboratoryOrderItem;
use Modules\Laboratory\Models\LaboratoryTestItem;
use Modules\Laboratory\Services\Web\LaboratoryOrderWebService;

class LaboratorySampleOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $laboratory = Laboratory::where('status', 'approved')->first();
        if (! $laboratory) {
            return;
        }

        $patient = User::where('phone', '07708888000')->first()
            ?? User::firstOrCreate(
                ['phone' => '07708888000'],
                [
                    'name' => 'مريض تجريبي',
                    'role' => 'patient',
                    'password' => bcrypt('password123'),
                ]
            );

        $catalogItems = LaboratoryTestItem::where('laboratory_id', $laboratory->id)
            ->with('labTest')
            ->limit(3)
            ->get();

        if ($catalogItems->isEmpty()) {
            return;
        }

        if (LaboratoryOrder::where('laboratory_id', $laboratory->id)->exists()) {
            return;
        }

        $orderWithItems = LaboratoryOrder::create([
            'order_number' => LaboratoryOrderWebService::generateOrderNumber(),
            'laboratory_id' => $laboratory->id,
            'patient_id' => $patient->id,
            'status' => LaboratoryOrderStatus::New,
            'source' => 'catalog',
            'patient_notes' => 'طلب تحاليل روتينية',
        ]);

        foreach ($catalogItems->take(2) as $item) {
            LaboratoryOrderItem::create([
                'laboratory_order_id' => $orderWithItems->id,
                'laboratory_test_item_id' => $item->id,
                'lab_test_id' => $item->lab_test_id,
                'test_name' => $item->labTest?->name_ar ?? 'تحليل',
                'price' => $item->price,
                'quantity' => 1,
                'result_hours' => $item->result_hours,
                'source' => 'patient_selected',
            ]);
        }

        LaboratoryOrder::create([
            'order_number' => LaboratoryOrderWebService::generateOrderNumber(),
            'laboratory_id' => $laboratory->id,
            'patient_id' => $patient->id,
            'status' => LaboratoryOrderStatus::New,
            'source' => 'prescription',
            'prescription_image' => null,
            'patient_notes' => 'مرفق صورة روشتة — يرجى تحديد التحاليل المطلوبة',
        ]);
    }
}
