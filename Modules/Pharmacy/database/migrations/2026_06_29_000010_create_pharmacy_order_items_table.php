<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pharmacy_order_id')->constrained('pharmacy_orders')->cascadeOnDelete();
            $table->foreignId('pharmacy_medicine_id')->nullable()->constrained('pharmacy_medicines')->nullOnDelete();
            $table->foreignId('medicine_id')->nullable()->constrained('medicines')->nullOnDelete();
            $table->string('medicine_name');
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->string('source', 32)->default('patient_selected');
            $table->timestamps();

            $table->index('pharmacy_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_order_items');
    }
};
