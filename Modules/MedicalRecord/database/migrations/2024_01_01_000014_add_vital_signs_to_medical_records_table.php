<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->decimal('weight', 5, 2)->nullable()->after('notes');
            $table->decimal('height', 5, 2)->nullable()->after('weight');
            $table->string('blood_pressure', 20)->nullable()->after('height');
            $table->text('allergies')->nullable()->after('blood_pressure');
        });
    }

    public function down(): void
    {
        Schema::table('medical_records', function (Blueprint $table) {
            $table->dropColumn(['weight', 'height', 'blood_pressure', 'allergies']);
        });
    }
};
