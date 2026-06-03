<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_ghost')->default(false)->after('status');
            $table->uuid('created_by_doctor_id')->nullable()->after('is_ghost');

            $table->foreign('created_by_doctor_id')->references('id')->on('users')->onDelete('set null');
            $table->index('is_ghost');
            $table->index('created_by_doctor_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['is_ghost']);
            $table->dropIndex(['created_by_doctor_id']);
            $table->dropForeign(['created_by_doctor_id']);
            $table->dropColumn(['is_ghost', 'created_by_doctor_id']);
        });
    }
};
