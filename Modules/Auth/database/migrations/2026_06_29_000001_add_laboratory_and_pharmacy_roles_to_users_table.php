<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'laboratory', 'pharmacy') NOT NULL DEFAULT 'patient'");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('users')->whereIn('role', ['laboratory', 'pharmacy'])->update(['role' => 'patient']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin') NOT NULL DEFAULT 'patient'");
        }
    }
};
