<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('governorates', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('is_active');
        });

        $now = now();
        $governorates = [
            ['name_ar' => 'بغداد', 'name_en' => 'Baghdad'],
            ['name_ar' => 'البصرة', 'name_en' => 'Basra'],
            ['name_ar' => 'نينوى', 'name_en' => 'Nineveh'],
            ['name_ar' => 'أربيل', 'name_en' => 'Erbil'],
            ['name_ar' => 'السليمانية', 'name_en' => 'Sulaymaniyah'],
            ['name_ar' => 'دهوك', 'name_en' => 'Duhok'],
            ['name_ar' => 'كركوك', 'name_en' => 'Kirkuk'],
            ['name_ar' => 'الأنبار', 'name_en' => 'Anbar'],
            ['name_ar' => 'بابل', 'name_en' => 'Babylon'],
            ['name_ar' => 'كربلاء', 'name_en' => 'Karbala'],
            ['name_ar' => 'النجف', 'name_en' => 'Najaf'],
            ['name_ar' => 'ذي قار', 'name_en' => 'Dhi Qar'],
            ['name_ar' => 'ميسان', 'name_en' => 'Maysan'],
            ['name_ar' => 'واسط', 'name_en' => 'Wasit'],
            ['name_ar' => 'ديالى', 'name_en' => 'Diyala'],
            ['name_ar' => 'صلاح الدين', 'name_en' => 'Saladin'],
            ['name_ar' => 'المثنى', 'name_en' => 'Muthanna'],
            ['name_ar' => 'القادسية', 'name_en' => 'Qadisiyyah'],
        ];

        foreach ($governorates as $governorate) {
            DB::table('governorates')->insert([
                ...$governorate,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('governorates');
    }
};
