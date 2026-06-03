<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appointment_id')->unique();
            $table->uuid('doctor_id');
            $table->uuid('patient_id');
            $table->enum('record_type', ['prescription', 'report', 'diagnosis'])->default('diagnosis');
            $table->text('diagnosis')->nullable();
            $table->json('prescription')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('appointment_id')->references('id')->on('appointments')->onDelete('cascade');
            $table->foreign('doctor_id')->references('id')->on('doctors')->onDelete('cascade');
            $table->foreign('patient_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');

            $table->index('doctor_id');
            $table->index('patient_id');
            $table->index('appointment_id');
            $table->index('record_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
