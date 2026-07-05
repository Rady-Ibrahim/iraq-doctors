<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->foreignId('governorate_id')->nullable()->after('name')->constrained('governorates')->nullOnDelete();
            $table->string('district')->nullable()->after('governorate_id');
            $table->string('address')->nullable()->after('district');
            $table->decimal('latitude', 10, 7)->nullable()->after('address');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->text('description_ar')->nullable()->after('longitude');
            $table->string('logo')->nullable()->after('description_ar');
            $table->string('commercial_register_document')->nullable()->after('logo');
            $table->string('license_document')->nullable()->after('commercial_register_document');
            $table->string('owner_id_document')->nullable()->after('license_document');
        });
    }

    public function down(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('governorate_id');
            $table->dropColumn([
                'district',
                'address',
                'latitude',
                'longitude',
                'description_ar',
                'logo',
                'commercial_register_document',
                'license_document',
                'owner_id_document',
            ]);
        });
    }
};
