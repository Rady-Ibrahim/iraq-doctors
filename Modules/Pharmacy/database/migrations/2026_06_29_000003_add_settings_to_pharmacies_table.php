<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('owner_id_document');
            $table->string('whatsapp')->nullable()->after('contact_phone');
            $table->json('working_hours')->nullable()->after('whatsapp');
            $table->boolean('delivery_enabled')->default(false)->after('working_hours');
            $table->decimal('delivery_fee', 10, 2)->nullable()->after('delivery_enabled');
            $table->decimal('min_order_for_delivery', 10, 2)->nullable()->after('delivery_fee');
            $table->foreignId('subscription_id')->nullable()->after('min_order_for_delivery')
                ->constrained('subscriptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('pharmacies', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn([
                'contact_phone',
                'whatsapp',
                'working_hours',
                'delivery_enabled',
                'delivery_fee',
                'min_order_for_delivery',
            ]);
        });
    }
};
