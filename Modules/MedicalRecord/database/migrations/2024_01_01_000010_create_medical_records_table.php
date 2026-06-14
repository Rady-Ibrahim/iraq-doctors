<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('medical_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('appointment_id')->unique()->constrained('appointments')->onDelete('cascade');
            $table->foreignId('doctor_id')->constrained('doctors')->onDelete('cascade');
            $table->foreignId('patient_id')->constrained('users')->onDelete('cascade');
            $table->enum('record_type', ['prescription', 'report', 'diagnosis'])->default('diagnosis');
            $table->text('diagnosis')->nullable();
            $table->json('prescription')->nullable();
            $table->text('notes')->nullable();
            $table->json('attachments')->nullable();
            $table->foreignId('created_by')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->index('record_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('medical_records');
    }
};
