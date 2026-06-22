<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_branches', function (Blueprint $table) {
            $table->foreignId('governorate_id')->nullable()->after('doctor_id')->constrained('governorates')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('doctor_branches', function (Blueprint $table) {
            $table->dropConstrainedForeignId('governorate_id');
        });
    }
};
