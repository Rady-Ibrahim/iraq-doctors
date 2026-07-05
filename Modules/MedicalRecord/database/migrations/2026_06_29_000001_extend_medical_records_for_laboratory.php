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
            $table->dropForeign(['appointment_id']);
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropUnique(['appointment_id']);
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable()->change();
            $table->dropForeign(['doctor_id']);
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->unsignedBigInteger('doctor_id')->nullable()->change();
            $table->foreignId('laboratory_id')->nullable()->after('doctor_id')
                ->constrained('laboratories')->nullOnDelete();
            $table->foreignId('laboratory_order_id')->nullable()->after('laboratory_id')
                ->constrained('laboratory_orders')->nullOnDelete();
        });

        Schema::table('medical_records', function (Blueprint $table) {
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->index('laboratory_order_id');
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medical_records MODIFY record_type ENUM('prescription', 'report', 'diagnosis', 'lab_result') NOT NULL DEFAULT 'diagnosis'");
        }
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropForeign(['laboratory_id']);
            $table->dropForeign(['laboratory_order_id']);
            $table->dropColumn(['laboratory_id', 'laboratory_order_id']);
        });

        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE medical_records MODIFY record_type ENUM('prescription', 'report', 'diagnosis') NOT NULL DEFAULT 'diagnosis'");
        }
    }
};
