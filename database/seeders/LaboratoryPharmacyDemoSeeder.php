<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Governorate;
use Modules\Laboratory\Database\Seeders\LaboratorySampleOrdersSeeder;
use Modules\Laboratory\Models\LabTest;
use Modules\Laboratory\Models\LabTestCategory;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratorySubscription;
use Modules\Laboratory\Models\LaboratoryTestItem;
use Modules\Pharmacy\Database\Seeders\PharmacySampleOrdersSeeder;
use Modules\Pharmacy\Models\Medicine;
use Modules\Pharmacy\Models\MedicineCategory;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacyMedicine;
use Modules\Pharmacy\Models\PharmacySubscription;
use Modules\Subscription\Models\Subscription;

/**
 * Demo laboratory + pharmacy accounts for QA and Postman.
 *
 * Laboratory web: 07708888002 / password123  (laboratory@iraq-doctors.test)
 * Pharmacy web:   07708888003 / password123  (pharmacy@iraq-doctors.test)
 */
class LaboratoryPharmacyDemoSeeder extends Seeder
{
    public function run(): void
    {
        $governorate = Governorate::where('name_en', 'Baghdad')->first()
            ?? Governorate::where('is_active', true)->first();

        if (! $governorate) {
            $this->command?->warn('LaboratoryPharmacyDemoSeeder: no governorate found — skip.');

            return;
        }

        $labPlan = Subscription::firstOrCreate(
            ['name' => 'Laboratory Monthly', 'type' => 'laboratory'],
            [
                'description_ar' => 'اشتراك شهري للمعامل',
                'description_en' => 'Monthly laboratory subscription',
                'price' => 75000,
                'duration_days' => 30,
                'status' => 'active',
                'sort_order' => 10,
            ]
        );

        $pharmacyPlan = Subscription::firstOrCreate(
            ['name' => 'Pharmacy Monthly', 'type' => 'pharmacy'],
            [
                'description_ar' => 'اشتراك شهري للصيدليات',
                'description_en' => 'Monthly pharmacy subscription',
                'price' => 60000,
                'duration_days' => 30,
                'status' => 'active',
                'sort_order' => 11,
            ]
        );

        $labCategory = LabTestCategory::firstOrCreate(
            ['name_ar' => 'تحاليل الدم'],
            ['name_en' => 'Blood Tests', 'sort_order' => 1, 'is_active' => true]
        );

        $labTests = collect([
            ['name_ar' => 'صورة دم كاملة', 'code' => 'CBC', 'sample_type' => 'blood'],
            ['name_ar' => 'سكر صائم', 'code' => 'FBS', 'sample_type' => 'blood'],
            ['name_ar' => 'وظائف كلى', 'code' => 'RFT', 'sample_type' => 'blood'],
        ])->map(fn ($row) => LabTest::firstOrCreate(
            ['code' => $row['code']],
            [
                ...$row,
                'lab_test_category_id' => $labCategory->id,
                'is_active' => true,
            ]
        ));

        $medCategory = MedicineCategory::firstOrCreate(
            ['name_ar' => 'مسكنات'],
            ['name_en' => 'Analgesics', 'sort_order' => 1, 'is_active' => true]
        );

        $medicines = collect([
            ['name_ar' => 'باراسيتامول 500mg', 'generic_name' => 'Paracetamol', 'dosage_form' => 'tablet', 'strength' => '500mg'],
            ['name_ar' => 'إيبوبروفين 400mg', 'generic_name' => 'Ibuprofen', 'dosage_form' => 'tablet', 'strength' => '400mg'],
            ['name_ar' => 'أموكسيسيلين 500mg', 'generic_name' => 'Amoxicillin', 'dosage_form' => 'capsule', 'strength' => '500mg'],
        ])->map(fn ($row) => Medicine::firstOrCreate(
            ['name_ar' => $row['name_ar']],
            [
                ...$row,
                'medicine_category_id' => $medCategory->id,
                'is_active' => true,
            ]
        ));

        $labUser = User::updateOrCreate(
            ['phone' => '07708888002'],
            [
                'name' => 'مسؤول معمل تجريبي',
                'email' => 'laboratory@iraq-doctors.test',
                'password' => Hash::make('password123'),
                'role' => 'laboratory',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $laboratory = Laboratory::updateOrCreate(
            ['user_id' => $labUser->id],
            [
                'name' => 'معمل بغداد التجريبي',
                'governorate_id' => $governorate->id,
                'district' => 'الكرادة',
                'address' => 'بغداد — الكرادة — شارع 62',
                'latitude' => 33.3152,
                'longitude' => 44.3661,
                'description_ar' => 'معمل تحاليل تجريبي للاختبار',
                'status' => 'approved',
                'contact_phone' => '07708888002',
                'home_collection_enabled' => true,
                'home_collection_fee' => 5000,
                'subscription_id' => $labPlan->id,
            ]
        );

        LaboratorySubscription::updateOrCreate(
            [
                'laboratory_id' => $laboratory->id,
                'subscription_id' => $labPlan->id,
                'status' => 'active',
            ],
            [
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'amount_paid' => $labPlan->price,
                'payment_method' => 'bank_transfer',
            ]
        );

        foreach ($labTests as $index => $test) {
            LaboratoryTestItem::updateOrCreate(
                ['laboratory_id' => $laboratory->id, 'lab_test_id' => $test->id],
                [
                    'price' => 15000 + ($index * 5000),
                    'result_hours' => 24,
                    'is_available' => true,
                ]
            );
        }

        $pharmacyUser = User::updateOrCreate(
            ['phone' => '07708888003'],
            [
                'name' => 'مسؤول صيدلية تجريبي',
                'email' => 'pharmacy@iraq-doctors.test',
                'password' => Hash::make('password123'),
                'role' => 'pharmacy',
                'status' => 'active',
                'phone_verified_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        $pharmacy = Pharmacy::updateOrCreate(
            ['user_id' => $pharmacyUser->id],
            [
                'name' => 'صيدلية النور التجريبية',
                'governorate_id' => $governorate->id,
                'district' => 'المنصور',
                'address' => 'بغداد — المنصور — شارع 14 رمضان',
                'latitude' => 33.3128,
                'longitude' => 44.3615,
                'description_ar' => 'صيدلية تجريبية مع توصيل',
                'status' => 'approved',
                'contact_phone' => '07708888003',
                'delivery_enabled' => true,
                'delivery_fee' => 3000,
                'min_order_for_delivery' => 10000,
                'subscription_id' => $pharmacyPlan->id,
            ]
        );

        PharmacySubscription::updateOrCreate(
            [
                'pharmacy_id' => $pharmacy->id,
                'subscription_id' => $pharmacyPlan->id,
                'status' => 'active',
            ],
            [
                'start_date' => now()->subDays(3),
                'end_date' => now()->addDays(27),
                'amount_paid' => $pharmacyPlan->price,
                'payment_method' => 'bank_transfer',
            ]
        );

        foreach ($medicines as $index => $medicine) {
            PharmacyMedicine::updateOrCreate(
                ['pharmacy_id' => $pharmacy->id, 'medicine_id' => $medicine->id],
                [
                    'price' => 2500 + ($index * 1000),
                    'stock_quantity' => 100,
                    'is_available' => true,
                ]
            );
        }

        $this->call(LaboratorySampleOrdersSeeder::class);
        $this->call(PharmacySampleOrdersSeeder::class);

        $this->command?->info('LaboratoryPharmacyDemoSeeder done.');
        $this->command?->info("  Laboratory ID: {$laboratory->id} — /laboratory/login (07708888002)");
        $this->command?->info("  Pharmacy ID: {$pharmacy->id} — /pharmacy/login (07708888003)");
        $this->command?->info('  Mobile patient: 07708888000 — search laboratories/pharmacies + create orders');
    }
}
