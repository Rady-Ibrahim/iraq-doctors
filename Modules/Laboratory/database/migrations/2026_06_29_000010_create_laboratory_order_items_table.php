<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_order_id')->constrained('laboratory_orders')->cascadeOnDelete();
            $table->foreignId('laboratory_test_item_id')->nullable()->constrained('laboratory_test_items')->nullOnDelete();
            $table->foreignId('lab_test_id')->nullable()->constrained('lab_tests')->nullOnDelete();
            $table->string('test_name');
            $table->decimal('price', 12, 2);
            $table->unsignedSmallInteger('quantity')->default(1);
            $table->unsignedInteger('result_hours')->nullable();
            $table->string('source', 32)->default('patient_selected');
            $table->timestamps();

            $table->index('laboratory_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_order_items');
    }
};
