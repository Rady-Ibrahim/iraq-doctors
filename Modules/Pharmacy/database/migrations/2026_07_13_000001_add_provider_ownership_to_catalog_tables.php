<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('medicines') && ! Schema::hasColumn('medicines', 'created_by_pharmacy_id')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->foreignId('created_by_pharmacy_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('pharmacies')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('lab_tests') && ! Schema::hasColumn('lab_tests', 'created_by_laboratory_id')) {
            Schema::table('lab_tests', function (Blueprint $table) {
                $table->foreignId('created_by_laboratory_id')
                    ->nullable()
                    ->after('is_active')
                    ->constrained('laboratories')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('medical_records')) {
            Schema::table('medical_records', function (Blueprint $table) {
                if (! Schema::hasColumn('medical_records', 'recommended_pharmacy_id')) {
                    $table->foreignId('recommended_pharmacy_id')
                        ->nullable()
                        ->after('pharmacy_order_id')
                        ->constrained('pharmacies')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('medical_records', 'recommended_laboratory_id')) {
                    $table->foreignId('recommended_laboratory_id')
                        ->nullable()
                        ->after('recommended_pharmacy_id')
                        ->constrained('laboratories')
                        ->nullOnDelete();
                }

                if (! Schema::hasColumn('medical_records', 'lab_tests_requested')) {
                    $table->json('lab_tests_requested')->nullable()->after('recommended_laboratory_id');
                }

                if (! Schema::hasColumn('medical_records', 'referral_status')) {
                    $table->string('referral_status', 32)->nullable()->after('lab_tests_requested');
                }
            });
        }

        if (Schema::hasTable('pharmacy_orders') && Schema::hasColumn('pharmacy_orders', 'prescription_id')) {
            Schema::table('pharmacy_orders', function (Blueprint $table) {
                $table->foreign('prescription_id')
                    ->references('id')
                    ->on('medical_records')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('laboratory_orders') && Schema::hasColumn('laboratory_orders', 'prescription_id')) {
            Schema::table('laboratory_orders', function (Blueprint $table) {
                $table->foreign('prescription_id')
                    ->references('id')
                    ->on('medical_records')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('laboratory_orders')) {
            Schema::table('laboratory_orders', function (Blueprint $table) {
                if (Schema::hasColumn('laboratory_orders', 'prescription_id')) {
                    $table->dropForeign(['prescription_id']);
                }
            });
        }

        if (Schema::hasTable('pharmacy_orders')) {
            Schema::table('pharmacy_orders', function (Blueprint $table) {
                if (Schema::hasColumn('pharmacy_orders', 'prescription_id')) {
                    $table->dropForeign(['prescription_id']);
                }
            });
        }

        if (Schema::hasTable('medical_records')) {
            Schema::table('medical_records', function (Blueprint $table) {
                if (Schema::hasColumn('medical_records', 'recommended_laboratory_id')) {
                    $table->dropForeign(['recommended_laboratory_id']);
                }
                if (Schema::hasColumn('medical_records', 'recommended_pharmacy_id')) {
                    $table->dropForeign(['recommended_pharmacy_id']);
                }
                $table->dropColumn(array_filter([
                    Schema::hasColumn('medical_records', 'recommended_pharmacy_id') ? 'recommended_pharmacy_id' : null,
                    Schema::hasColumn('medical_records', 'recommended_laboratory_id') ? 'recommended_laboratory_id' : null,
                    Schema::hasColumn('medical_records', 'lab_tests_requested') ? 'lab_tests_requested' : null,
                    Schema::hasColumn('medical_records', 'referral_status') ? 'referral_status' : null,
                ]));
            });
        }

        if (Schema::hasTable('lab_tests') && Schema::hasColumn('lab_tests', 'created_by_laboratory_id')) {
            Schema::table('lab_tests', function (Blueprint $table) {
                $table->dropForeign(['created_by_laboratory_id']);
                $table->dropColumn('created_by_laboratory_id');
            });
        }

        if (Schema::hasTable('medicines') && Schema::hasColumn('medicines', 'created_by_pharmacy_id')) {
            Schema::table('medicines', function (Blueprint $table) {
                $table->dropForeign(['created_by_pharmacy_id']);
                $table->dropColumn('created_by_pharmacy_id');
            });
        }
    }
};
