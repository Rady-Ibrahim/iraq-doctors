<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pharmacy_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('pharmacy_id')->constrained('pharmacies')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('pharmacy_branch_id')->nullable()->constrained('pharmacy_branches')->nullOnDelete();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->string('prescription_image')->nullable();
            $table->string('fulfillment_type', 16)->default('pickup');
            $table->text('delivery_address')->nullable();
            $table->decimal('delivery_latitude', 10, 7)->nullable();
            $table->decimal('delivery_longitude', 10, 7)->nullable();
            $table->text('delivery_notes')->nullable();
            $table->string('status', 32)->default('new');
            $table->string('source', 32)->default('catalog');
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('delivery_fee', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->text('patient_notes')->nullable();
            $table->text('quote_notes')->nullable();
            $table->text('pharmacy_notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('out_for_delivery_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['pharmacy_id', 'status']);
            $table->index('fulfillment_type');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pharmacy_orders');
    }
};
