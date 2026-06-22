<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Appointment\Models\Appointment;
use Modules\Auth\Models\User;
use Modules\Doctor\Models\Doctor;
use Modules\Doctor\Models\DoctorSchedule;
use Modules\Doctor\Models\Speciality;
use Modules\Subscription\Models\DoctorSubscription;
use Modules\Subscription\Models\Subscription;

/**
 * Demo accounts (password for all: password123)
 *
 * Admin web:  /admin/login     → phone: 07700000001
 * Doctor web: /doctor/login    → phone: 07701234567
 * Patient API: /api/v1/auth/login → phone: 07700000000 / email: test@example.com
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $speciality = Speciality::firstOrCreate(
            ['name_ar' => 'طب عام'],
            ['name_en' => 'General Medicine', 'is_active' => true]
        );

        Speciality::firstOrCreate(
            ['name_ar' => 'أمراض القلب'],
            ['name_en' => 'Cardiology', 'is_active' => true]
        );

        User::firstOrCreate(
            ['phone' => '07700000001'],
            [
                'name' => 'مدير النظام',
                'email' => 'admin@iraq-doctors.test',
                'password' => Hash::make('password123'),
                'role' => 'admin',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $doctorUser = User::firstOrCreate(
            ['phone' => '07701234567'],
            [
                'name' => 'د. أحمد محمد',
                'email' => 'doctor@iraq-doctors.test',
                'password' => Hash::make('password123'),
                'role' => 'doctor',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $professionalPlan = Subscription::where('name', 'Professional')->first()
            ?? Subscription::first();

        $doctor = Doctor::firstOrCreate(
            ['user_id' => $doctorUser->id],
            [
                'speciality_id' => $speciality->id,
                'bio_ar' => 'طبيب عام بخبرة 10 سنوات في بغداد',
                'experience_years' => 10,
                'consultation_fee' => 25000,
                'rating' => 4.5,
                'rating_count' => 12,
                'address' => 'بغداد - الكرادة',
                'status' => 'approved',
                'subscription_id' => $professionalPlan?->id,
            ]
        );

        if ($professionalPlan && !DoctorSubscription::where('doctor_id', $doctor->id)->exists()) {
            DoctorSubscription::create([
                'doctor_id' => $doctor->id,
                'subscription_id' => $professionalPlan->id,
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'status' => 'active',
                'amount_paid' => $professionalPlan->price,
                'payment_method' => 'vodafone_cash',
                'transaction_id' => 'DEMO-TXN-001',
            ]);
        }

        $schedule = DoctorSchedule::firstOrCreate(
            [
                'doctor_id' => $doctor->id,
                'day_of_week' => 'Sunday',
            ],
            [
                'start_time' => '09:00:00',
                'end_time' => '17:00:00',
                'is_active' => true,
            ]
        );

        $patient = User::firstOrCreate(
            ['phone' => '07700000000'],
            [
                'name' => 'مريض تجريبي',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'role' => 'patient',
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        if (!Appointment::where('doctor_id', $doctor->id)->where('patient_id', $patient->id)->exists()) {
            Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'doctor_schedule_id' => $schedule->id,
                'appointment_date' => today(),
                'appointment_time' => '10:00:00',
                'status' => 'completed',
                'price' => 25000,
                'notes' => 'موعد تجريبي',
            ]);

            Appointment::create([
                'doctor_id' => $doctor->id,
                'patient_id' => $patient->id,
                'doctor_schedule_id' => $schedule->id,
                'appointment_date' => today()->addDay(),
                'appointment_time' => '11:30:00',
                'status' => 'confirmed',
                'price' => 25000,
                'notes' => 'موعد قادم',
            ]);
        }

        $this->command?->info('Demo data seeded.');
        $this->command?->info('Admin: 07700000001 / password123');
        $this->command?->info('Doctor: 07701234567 / password123');
        $this->command?->info('Patient: 07700000000 / password123');
    }
}
