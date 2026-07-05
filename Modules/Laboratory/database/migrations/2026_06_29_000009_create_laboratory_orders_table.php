<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
            $table->foreignId('patient_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('laboratory_branch_id')->nullable()->constrained('laboratory_branches')->nullOnDelete();
            $table->unsignedBigInteger('prescription_id')->nullable();
            $table->string('prescription_image')->nullable();
            $table->string('status', 32)->default('new');
            $table->string('source', 32)->default('catalog');
            $table->decimal('subtotal', 12, 2)->nullable();
            $table->decimal('home_collection_fee', 12, 2)->nullable();
            $table->decimal('total_amount', 12, 2)->nullable();
            $table->text('patient_notes')->nullable();
            $table->text('quote_notes')->nullable();
            $table->text('lab_notes')->nullable();
            $table->text('cancel_reason')->nullable();
            $table->timestamp('quoted_at')->nullable();
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index(['laboratory_id', 'status']);
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_orders');
    }
};
