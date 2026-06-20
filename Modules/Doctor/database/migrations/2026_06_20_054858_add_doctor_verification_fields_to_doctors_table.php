<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->string('license_document')->nullable()->after('speciality_id');
            $table->string('clinic_image')->nullable()->after('license_document');
            $table->text('reject_reason')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropColumn([
                'license_document',
                'clinic_image',
                'reject_reason',
            ]);
        });
    }
};