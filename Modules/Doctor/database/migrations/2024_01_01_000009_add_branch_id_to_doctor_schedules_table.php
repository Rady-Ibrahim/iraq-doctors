<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->uuid('doctor_branch_id')->nullable()->after('doctor_id');
            $table->foreign('doctor_branch_id')->references('id')->on('doctor_branches')->onDelete('set null');
            $table->index('doctor_branch_id');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_schedules', function (Blueprint $table) {
            $table->dropForeign(['doctor_branch_id']);
            $table->dropIndex(['doctor_branch_id']);
            $table->dropColumn('doctor_branch_id');
        });
    }
};
