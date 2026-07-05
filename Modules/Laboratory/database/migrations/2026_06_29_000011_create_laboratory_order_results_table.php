<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_order_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_order_id')->constrained('laboratory_orders')->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->string('mime_type')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('uploaded_by')->constrained('users')->cascadeOnDelete();
            $table->foreignId('medical_record_id')->nullable()->constrained('medical_records')->nullOnDelete();
            $table->timestamps();

            $table->index('laboratory_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_order_results');
    }
};
