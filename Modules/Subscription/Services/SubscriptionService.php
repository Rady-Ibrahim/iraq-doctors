<?php

namespace Modules\Subscription\Services;

use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\DoctorSubscription;
use Modules\Doctor\Models\Doctor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SubscriptionService
{
    public function getAllPlans()
    {
        return Subscription::active()
            ->orderByVisibility()
            ->get();
    }

    public function getPlanById($id)
    {
        return Subscription::findOrFail($id);
    }

    public function createPlan(array $data)
    {
        return DB::transaction(function () use ($data) {
            return Subscription::create([
                'id' => Str::uuid(),
                'name' => $data['name'],
                'description_ar' => $data['description_ar'] ?? null,
                'description_en' => $data['description_en'] ?? null,
                'price' => $data['price'],
                'duration_days' => $data['duration_days'],
                'max_appointments' => $data['max_appointments'] ?? null,
                'is_featured' => $data['is_featured'] ?? false,
                'has_analytics' => $data['has_analytics'] ?? false,
                'has_banner' => $data['has_banner'] ?? false,
                'visibility_score' => $data['visibility_score'] ?? 1,
                'features' => $data['features'] ?? null,
                'status' => $data['status'] ?? 'active',
                'sort_order' => $data['sort_order'] ?? 0,
            ]);
        });
    }

    public function updatePlan($id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $plan = Subscription::findOrFail($id);
            $plan->update($data);
            return $plan;
        });
    }

    public function deletePlan($id)
    {
        return DB::transaction(function () use ($id) {
            $plan = Subscription::findOrFail($id);
            $plan->delete();
            return true;
        });
    }

    public function subscribeDoctor($doctorId, $subscriptionId, array $paymentData = [])
    {
        return DB::transaction(function () use ($doctorId, $subscriptionId, $paymentData) {
            $doctor = Doctor::findOrFail($doctorId);
            $subscription = Subscription::findOrFail($subscriptionId);

            // Check if doctor has active subscription
            $activeSubscription = $doctor->doctorSubscriptions()->active()->first();
            if ($activeSubscription) {
                throw new \Exception('Doctor already has an active subscription');
            }

            $startDate = now();
            $endDate = now()->addDays($subscription->duration_days);

            $doctorSubscription = DoctorSubscription::create([
                'id' => Str::uuid(),
                'doctor_id' => $doctorId,
                'subscription_id' => $subscriptionId,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'status' => 'active',
                'amount_paid' => $paymentData['amount'] ?? $subscription->price,
                'payment_method' => $paymentData['payment_method'] ?? null,
                'transaction_id' => $paymentData['transaction_id'] ?? null,
                'auto_renew' => $paymentData['auto_renew'] ?? false,
            ]);

            // Update doctor's current subscription
            $doctor->subscription_id = $subscriptionId;
            $doctor->save();

            return $doctorSubscription;
        });
    }

    public function renewSubscription($doctorId)
    {
        return DB::transaction(function () use ($doctorId) {
            $doctor = Doctor::findOrFail($doctorId);
            $currentSubscription = $doctor->doctorSubscriptions()->active()->first();

            if (!$currentSubscription) {
                throw new \Exception('No active subscription found');
            }

            $subscription = $currentSubscription->subscription;
            $newEndDate = now()->addDays($subscription->duration_days);

            $newSubscription = DoctorSubscription::create([
                'id' => Str::uuid(),
                'doctor_id' => $doctorId,
                'subscription_id' => $subscription->id,
                'start_date' => now(),
                'end_date' => $newEndDate,
                'status' => 'active',
                'amount_paid' => $subscription->price,
                'payment_method' => $currentSubscription->payment_method,
                'transaction_id' => null,
                'auto_renew' => $currentSubscription->auto_renew,
            ]);

            // Mark old subscription as cancelled
            $currentSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            return $newSubscription;
        });
    }

    public function cancelSubscription($doctorId)
    {
        return DB::transaction(function () use ($doctorId) {
            $doctor = Doctor::findOrFail($doctorId);
            $activeSubscription = $doctor->doctorSubscriptions()->active()->first();

            if (!$activeSubscription) {
                throw new \Exception('No active subscription found');
            }

            $activeSubscription->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            // Remove subscription from doctor
            $doctor->subscription_id = null;
            $doctor->save();

            return true;
        });
    }

    public function getDoctorSubscription($doctorId)
    {
        return DoctorSubscription::with('subscription')
            ->where('doctor_id', $doctorId)
            ->active()
            ->first();
    }

    public function checkDoctorSubscriptionLimit($doctorId)
    {
        $subscription = $this->getDoctorSubscription($doctorId);
        
        if (!$subscription) {
            return false;
        }

        $plan = $subscription->subscription;
        
        // If max_appointments is null, it means unlimited
        if ($plan->max_appointments === null) {
            return true;
        }

        // Count appointments in current month
        $monthlyAppointments = \Modules\Appointment\Models\Appointment::where('doctor_id', $doctorId)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();

        return $monthlyAppointments < $plan->max_appointments;
    }

    public function seedDefaultPlans()
    {
        $plans = [
            [
                'name' => 'Basic',
                'description_ar' => 'الباقة الأساسية - ظهور عادي',
                'description_en' => 'Basic Plan - Normal visibility',
                'price' => 0,
                'duration_days' => 30,
                'max_appointments' => 50,
                'is_featured' => false,
                'has_analytics' => false,
                'has_banner' => false,
                'visibility_score' => 1,
                'features' => json_encode([
                    'normal_visibility',
                    '50_appointments_per_month',
                    'basic_support'
                ]),
                'status' => 'active',
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional',
                'description_ar' => 'الباقة الاحترافية - ظهور أعلى',
                'description_en' => 'Professional Plan - Higher visibility',
                'price' => 50000,
                'duration_days' => 30,
                'max_appointments' => null,
                'is_featured' => false,
                'has_analytics' => true,
                'has_banner' => false,
                'visibility_score' => 2,
                'features' => json_encode([
                    'higher_visibility',
                    'unlimited_appointments',
                    'analytics_dashboard',
                    'priority_support'
                ]),
                'status' => 'active',
                'sort_order' => 2,
            ],
            [
                'name' => 'Premium',
                'description_ar' => 'الباقة المميزة - Featured Doctor',
                'description_en' => 'Premium Plan - Featured Doctor',
                'price' => 100000,
                'duration_days' => 30,
                'max_appointments' => null,
                'is_featured' => true,
                'has_analytics' => true,
                'has_banner' => true,
                'visibility_score' => 3,
                'features' => json_encode([
                    'featured_badge',
                    'unlimited_appointments',
                    'advanced_analytics',
                    'banner_display',
                    'dedicated_support',
                    'priority_listing'
                ]),
                'status' => 'active',
                'sort_order' => 3,
            ],
        ];

        foreach ($plans as $plan) {
            Subscription::create(array_merge($plan, ['id' => Str::uuid()]));
        }
    }
}
