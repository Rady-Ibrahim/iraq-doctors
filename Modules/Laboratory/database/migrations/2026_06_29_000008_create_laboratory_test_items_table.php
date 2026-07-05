<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laboratory_test_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laboratory_id')->constrained('laboratories')->cascadeOnDelete();
            $table->foreignId('lab_test_id')->constrained('lab_tests')->cascadeOnDelete();
            $table->decimal('price', 12, 2);
            $table->unsignedInteger('result_hours')->default(24);
            $table->boolean('is_available')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['laboratory_id', 'lab_test_id']);
            $table->index('is_available');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laboratory_test_items');
    }
};
