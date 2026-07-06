<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'laboratory', 'pharmacy', 'doctor_staff') NOT NULL DEFAULT 'patient'");
        }

        if (! Schema::hasTable('doctor_staff_members')) {
            Schema::create('doctor_staff_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('doctor_id')->constrained('doctors')->cascadeOnDelete();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->json('permissions');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();

                $table->index(['doctor_id', 'status']);
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('doctor_staff_members')) {
            Schema::dropIfExists('doctor_staff_members');
        }

        if (! Schema::hasTable('users')) {
            return;
        }

        $driver = Schema::getConnection()->getDriverName();

        if ($driver === 'mysql') {
            DB::table('users')->where('role', 'doctor_staff')->update(['role' => 'patient']);
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('patient', 'doctor', 'admin', 'laboratory', 'pharmacy') NOT NULL DEFAULT 'patient'");
        }
    }
};
