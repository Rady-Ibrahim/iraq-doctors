<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('avatar')->nullable()->after('name');
            $table->date('birthdate')->nullable()->after('avatar');
            $table->enum('gender', ['male', 'female'])->nullable()->after('birthdate');
            $table->string('city')->nullable()->after('gender');
            $table->string('district')->nullable()->after('city');
            $table->string('address')->nullable()->after('district');

            $table->index('city');
            $table->index('gender');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['city']);
            $table->dropIndex(['gender']);
            $table->dropColumn(['avatar', 'birthdate', 'gender', 'city', 'district', 'address']);
        });
    }
};
