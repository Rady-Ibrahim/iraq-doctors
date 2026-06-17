<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            // Make phone nullable — OTP can be sent via email instead
            $table->string('phone')->nullable()->change();
            // Add email column
            $table->string('email')->nullable()->after('phone')->index();
        });
    }

    public function down(): void
    {
        Schema::table('otps', function (Blueprint $table) {
            $table->string('phone')->nullable(false)->change();
            $table->dropColumn('email');
        });
    }
};
