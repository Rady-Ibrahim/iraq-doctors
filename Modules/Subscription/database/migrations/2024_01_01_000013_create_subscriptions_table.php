<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description_ar')->nullable();
            $table->text('description_en')->nullable();
            $table->decimal('price', 10, 2)->default(0);
            $table->integer('duration_days')->default(30);
            $table->integer('max_appointments')->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('has_analytics')->default(false);
            $table->boolean('has_banner')->default(false);
            $table->integer('visibility_score')->default(1);
            $table->json('features')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index('status');
            $table->index('visibility_score');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
