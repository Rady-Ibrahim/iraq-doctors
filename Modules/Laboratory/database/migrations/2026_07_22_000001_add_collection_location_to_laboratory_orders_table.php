<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratory_orders', function (Blueprint $table) {
            // نوع الجمع: clinic = المريض يأتي للعيادة، home = أخذ عينة في المنزل
            $table->string('collection_type', 10)->default('clinic')->after('prescription_image');
            $table->string('patient_address', 1000)->nullable()->after('collection_type');
            $table->decimal('patient_latitude', 10, 7)->nullable()->after('patient_address');
            $table->decimal('patient_longitude', 10, 7)->nullable()->after('patient_latitude');
        });
    }

    public function down(): void
    {
        Schema::table('laboratory_orders', function (Blueprint $table) {
            $table->dropColumn(['collection_type', 'patient_address', 'patient_latitude', 'patient_longitude']);
        });
    }
};
