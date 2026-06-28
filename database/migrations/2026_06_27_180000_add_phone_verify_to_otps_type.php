<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE otps MODIFY COLUMN type ENUM(
            'register',
            'login',
            'reset_password',
            'password_reset',
            'phone_verify'
        ) NOT NULL DEFAULT 'login'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE otps MODIFY COLUMN type ENUM(
            'register',
            'login',
            'reset_password',
            'password_reset'
        ) NOT NULL DEFAULT 'login'");
    }
};
