<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->timestamps();
        });

        $now = now();
        $defaults = [
            ['key' => 'vodafone_cash_number', 'value' => '07700000000'],
            ['key' => 'bank_name', 'value' => ''],
            ['key' => 'bank_account_name', 'value' => ''],
            ['key' => 'bank_account_number', 'value' => ''],
        ];

        foreach ($defaults as $row) {
            DB::table('app_settings')->insert([
                ...$row,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_settings');
    }
};
