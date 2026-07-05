<?php

namespace Modules\Pharmacy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Auth\Models\User;
use Modules\Pharmacy\Enums\PharmacyOrderStatus;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacyMedicine;
use Modules\Pharmacy\Models\PharmacyOrder;
use Modules\Pharmacy\Models\PharmacyOrderItem;
use Modules\Pharmacy\Services\Web\PharmacyOrderWebService;

class PharmacySampleOrdersSeeder extends Seeder
{
    public function run(): void
    {
        $pharmacy = Pharmacy::where('status', 'approved')->first();
        if (! $pharmacy) {
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

        $catalogItems = PharmacyMedicine::where('pharmacy_id', $pharmacy->id)
            ->with('medicine')
            ->where('is_available', true)
            ->where('stock_quantity', '>', 0)
            ->limit(3)
            ->get();

        if ($catalogItems->isEmpty()) {
            return;
        }

        if (PharmacyOrder::where('pharmacy_id', $pharmacy->id)->exists()) {
            return;
        }

        $pickupOrder = PharmacyOrder::create([
            'order_number' => PharmacyOrderWebService::generateOrderNumber(),
            'pharmacy_id' => $pharmacy->id,
            'patient_id' => $patient->id,
            'status' => PharmacyOrderStatus::New,
            'source' => 'catalog',
            'fulfillment_type' => 'pickup',
            'patient_notes' => 'طلب أدوية — استلام من الفرع',
        ]);

        foreach ($catalogItems->take(2) as $item) {
            PharmacyOrderItem::create([
                'pharmacy_order_id' => $pickupOrder->id,
                'pharmacy_medicine_id' => $item->id,
                'medicine_id' => $item->medicine_id,
                'medicine_name' => $item->medicine?->name_ar ?? 'دواء',
                'price' => $item->price,
                'quantity' => 1,
                'source' => 'patient_selected',
            ]);
        }

        PharmacyOrder::create([
            'order_number' => PharmacyOrderWebService::generateOrderNumber(),
            'pharmacy_id' => $pharmacy->id,
            'patient_id' => $patient->id,
            'status' => PharmacyOrderStatus::New,
            'source' => 'prescription',
            'fulfillment_type' => 'delivery',
            'delivery_address' => 'بغداد — الكرادة — شارع 14',
            'delivery_latitude' => 33.3152,
            'delivery_longitude' => 44.3661,
            'patient_notes' => 'روشتة مرفقة — يرجى التوصيل للمنزل',
        ]);
    }
}
