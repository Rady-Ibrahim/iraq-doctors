<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('doctor_subscriptions', function (Blueprint $table) {
            $table->date('start_date')->nullable()->change();
            $table->date('end_date')->nullable()->change();
            $table->string('payment_receipt')->nullable()->after('transaction_id');
            $table->decimal('submitted_amount', 10, 2)->nullable()->after('payment_receipt');
            $table->text('payment_reject_reason')->nullable()->after('submitted_amount');
            $table->foreignId('reviewed_by')->nullable()->after('payment_reject_reason')->constrained('users')->nullOnDelete();
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('doctor_subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reviewed_by');
            $table->dropColumn([
                'payment_receipt',
                'submitted_amount',
                'payment_reject_reason',
                'reviewed_at',
            ]);
        });
    }
};
