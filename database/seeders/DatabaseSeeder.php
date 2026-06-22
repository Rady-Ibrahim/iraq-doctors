<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
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
        $this->call(DemoDataSeeder::class);
    }
}
