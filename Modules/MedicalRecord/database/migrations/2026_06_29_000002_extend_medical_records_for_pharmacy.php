<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->foreignId('pharmacy_id')->nullable()->after('laboratory_order_id')
                ->constrained('pharmacies')->nullOnDelete();
            $table->foreignId('pharmacy_order_id')->nullable()->after('pharmacy_id')
                ->constrained('pharmacy_orders')->nullOnDelete();
            $table->index('pharmacy_order_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medical_records MODIFY record_type ENUM('prescription', 'report', 'diagnosis', 'lab_result', 'pharmacy_order') NOT NULL DEFAULT 'diagnosis'");
        }
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign(['pharmacy_id']);
            $table->dropForeign(['pharmacy_order_id']);
            $table->dropColumn(['pharmacy_id', 'pharmacy_order_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medical_records MODIFY record_type ENUM('prescription', 'report', 'diagnosis', 'lab_result') NOT NULL DEFAULT 'diagnosis'");
        }
    }
};
