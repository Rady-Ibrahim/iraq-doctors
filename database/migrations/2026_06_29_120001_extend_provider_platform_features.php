<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacies', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0)->after('status');
                $table->unsignedInteger('rating_count')->default(0)->after('rating');
            }
        });

        Schema::table('laboratories', function (Blueprint $table) {
            if (! Schema::hasColumn('laboratories', 'rating')) {
                $table->decimal('rating', 3, 2)->default(0)->after('status');
                $table->unsignedInteger('rating_count')->default(0)->after('rating');
            }
        });

        Schema::table('pharmacy_medicines', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_medicines', 'expiry_date')) {
                $table->date('expiry_date')->nullable()->after('stock_quantity');
                $table->index('expiry_date');
            }
        });

        Schema::table('pharmacy_branches', function (Blueprint $table) {
            if (! Schema::hasColumn('pharmacy_branches', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('is_active');
            }
        });

        Schema::table('laboratory_branches', function (Blueprint $table) {
            if (! Schema::hasColumn('laboratory_branches', 'working_hours')) {
                $table->json('working_hours')->nullable()->after('is_active');
            }
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropForeign(['doctor_id']);
        });

        DB::statement('ALTER TABLE reviews MODIFY appointment_id BIGINT UNSIGNED NULL');
        DB::statement('ALTER TABLE reviews MODIFY doctor_id BIGINT UNSIGNED NULL');

        Schema::table('reviews', function (Blueprint $table) {
            $table->foreign('appointment_id')->references('id')->on('appointments')->nullOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->nullOnDelete();
            $table->foreignId('pharmacy_order_id')->nullable()->after('doctor_id')->constrained('pharmacy_orders')->nullOnDelete();
            $table->foreignId('laboratory_order_id')->nullable()->after('pharmacy_order_id')->constrained('laboratory_orders')->nullOnDelete();
            $table->foreignId('pharmacy_id')->nullable()->after('laboratory_order_id')->constrained('pharmacies')->nullOnDelete();
            $table->foreignId('laboratory_id')->nullable()->after('pharmacy_id')->constrained('laboratories')->nullOnDelete();
            $table->unique('pharmacy_order_id');
            $table->unique('laboratory_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('reviews', function (Blueprint $table) {
            $table->dropUnique(['pharmacy_order_id']);
            $table->dropUnique(['laboratory_order_id']);
            $table->dropConstrainedForeignId('pharmacy_order_id');
            $table->dropConstrainedForeignId('laboratory_order_id');
            $table->dropConstrainedForeignId('pharmacy_id');
            $table->dropConstrainedForeignId('laboratory_id');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropForeign(['appointment_id']);
            $table->dropForeign(['doctor_id']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->unsignedBigInteger('appointment_id')->nullable(false)->change();
            $table->unsignedBigInteger('doctor_id')->nullable(false)->change();
            $table->foreign('appointment_id')->references('id')->on('appointments')->cascadeOnDelete();
            $table->foreign('doctor_id')->references('id')->on('doctors')->cascadeOnDelete();
        });

        Schema::table('pharmacy_branches', function (Blueprint $table) {
            $table->dropColumn('working_hours');
        });

        Schema::table('laboratory_branches', function (Blueprint $table) {
            $table->dropColumn('working_hours');
        });

        Schema::table('pharmacy_medicines', function (Blueprint $table) {
            $table->dropColumn('expiry_date');
        });

        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_count']);
        });

        Schema::table('laboratories', function (Blueprint $table) {
            $table->dropColumn(['rating', 'rating_count']);
        });
    }
};
