<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Auth\Models\User;
use Modules\StaticPage\Database\Seeders\StaticPageSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(StaticPageSeeder::class);
        $this->call(SubscriptionPlanSeeder::class);

        User::query()->firstOrCreate(
            ['phone' => '07700000000'],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Test Patient',
                'email' => 'test@example.com',
                'password' => Hash::make('password123'),
                'role' => 'patient',
                'status' => 'active',
                'email_verified_at' => now(),
                'phone_verified_at' => now(),
            ]
        );
    }
}
