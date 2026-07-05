<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laboratories', function (Blueprint $table) {
            $table->string('contact_phone')->nullable()->after('accreditation_document');
            $table->string('whatsapp')->nullable()->after('contact_phone');
            $table->json('working_hours')->nullable()->after('whatsapp');
            $table->boolean('home_collection_enabled')->default(false)->after('working_hours');
            $table->decimal('home_collection_fee', 10, 2)->nullable()->after('home_collection_enabled');
            $table->foreignId('subscription_id')->nullable()->after('home_collection_fee')
                ->constrained('subscriptions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laboratories', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_id');
            $table->dropColumn([
                'contact_phone',
                'whatsapp',
                'working_hours',
                'home_collection_enabled',
                'home_collection_fee',
            ]);
        });
    }
};
