<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->enum('consultation_type', ['clinic', 'home', 'online'])->default('clinic')->after('consultation_fee');
        });

        Schema::table('doctors', function (Blueprint $table) {
            $table->index('consultation_fee');
            $table->index('rating');
            $table->index('experience_years');
            $table->index('consultation_type');
        });

        Schema::table('doctor_branches', function (Blueprint $table) {
            $table->index('governorate');
        });
    }

    public function down(): void
    {
        Schema::table('doctors', function (Blueprint $table) {
            $table->dropIndex(['consultation_fee']);
            $table->dropIndex(['rating']);
            $table->dropIndex(['experience_years']);
            $table->dropIndex(['consultation_type']);
            $table->dropColumn('consultation_type');
        });

        Schema::table('doctor_branches', function (Blueprint $table) {
            $table->dropIndex(['governorate']);
        });
    }
};
