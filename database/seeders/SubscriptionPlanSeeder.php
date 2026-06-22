<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Subscription\Models\Subscription;
use Illuminate\Support\Str;

class SubscriptionPlanSeeder extends Seeder
{
    public function run(): void
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
            Subscription::firstOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }
    }
}
