<?php

namespace Modules\Pharmacy\Services\Web;

use App\Notifications\PharmacySubscriptionRequested;
use App\Services\AdminNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\Models\Pharmacy;
use Modules\Pharmacy\Models\PharmacySubscription;
use Modules\Subscription\Models\Subscription;

class PharmacySubscriptionService
{
    public function submitPaymentRequest(
        int $pharmacyId,
        int $subscriptionId,
        float $submittedAmount,
        UploadedFile $receipt,
        string $paymentMethod = 'bank_transfer'
    ): PharmacySubscription {
        return DB::transaction(function () use ($pharmacyId, $subscriptionId, $submittedAmount, $receipt, $paymentMethod) {
            $pharmacy = Pharmacy::findOrFail($pharmacyId);
            $subscription = Subscription::ofType('pharmacy')->findOrFail($subscriptionId);

            if ($pharmacy->pharmacySubscriptions()->active()->exists()) {
                throw new \InvalidArgumentException('لديك اشتراك نشط بالفعل');
            }

            if ($pharmacy->pharmacySubscriptions()->pendingPayment()->exists()) {
                throw new \InvalidArgumentException('لديك طلب دفع قيد المراجعة بالفعل');
            }

            if ((float) $submittedAmount !== (float) $subscription->price) {
                throw new \InvalidArgumentException('المبلغ المُدخل يجب أن يساوي سعر الباقة بالضبط');
            }

            $pharmacySubscription = PharmacySubscription::create([
                'pharmacy_id' => $pharmacyId,
                'subscription_id' => $subscriptionId,
                'status' => 'pending_payment',
                'amount_paid' => 0,
                'submitted_amount' => $submittedAmount,
                'payment_method' => $paymentMethod,
                'payment_receipt' => $receipt->store('subscriptions/receipts', 'public'),
            ]);

            AdminNotificationService::notify(new PharmacySubscriptionRequested($pharmacySubscription));

            return $pharmacySubscription;
        });
    }

    public function confirmPayment(int $pharmacySubscriptionId, int $adminUserId): PharmacySubscription
    {
        return DB::transaction(function () use ($pharmacySubscriptionId, $adminUserId) {
            $pharmacySubscription = PharmacySubscription::with('subscription')
                ->where('status', 'pending_payment')
                ->findOrFail($pharmacySubscriptionId);

            $subscription = $pharmacySubscription->subscription;
            $startDate = now();
            $endDate = now()->addDays($subscription->duration_days);

            $pharmacySubscription->update([
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'amount_paid' => $pharmacySubscription->submitted_amount ?? $subscription->price,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
                'payment_reject_reason' => null,
                'expiry_reminder_sent_at' => null,
            ]);

            $pharmacySubscription->pharmacy->update(['subscription_id' => $subscription->id]);

            return $pharmacySubscription->fresh(['pharmacy.user', 'subscription']);
        });
    }

    public function rejectPayment(int $pharmacySubscriptionId, int $adminUserId, ?string $reason = null): PharmacySubscription
    {
        return DB::transaction(function () use ($pharmacySubscriptionId, $adminUserId, $reason) {
            $pharmacySubscription = PharmacySubscription::where('status', 'pending_payment')
                ->findOrFail($pharmacySubscriptionId);

            $pharmacySubscription->update([
                'status' => 'cancelled',
                'payment_reject_reason' => $reason,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
                'cancelled_at' => now(),
            ]);

            return $pharmacySubscription->fresh(['pharmacy.user', 'subscription']);
        });
    }

    public function getSubscriptionStatus(int $pharmacyId): ?array
    {
        $pharmacy = Pharmacy::findOrFail($pharmacyId);

        $pending = $pharmacy->pharmacySubscriptions()->pendingPayment()->with('subscription')->latest()->first();
        if ($pending) {
            return [
                'status' => 'pending_payment',
                'plan_name' => $pending->subscription?->name,
                'price' => $pending->subscription?->price,
                'submitted_amount' => $pending->submitted_amount,
                'payment_method' => $pending->payment_method,
                'payment_reject_reason' => $pending->payment_reject_reason,
                'created_at' => $pending->created_at?->format('Y-m-d'),
            ];
        }

        $active = $pharmacy->pharmacySubscriptions()->active()->with('subscription')->first();
        if ($active) {
            return [
                'status' => 'active',
                'plan_name' => $active->subscription?->name,
                'price' => $active->amount_paid,
                'start_date' => $active->start_date?->format('Y-m-d'),
                'end_date' => $active->end_date?->format('Y-m-d'),
                'days_remaining' => $active->days_remaining,
            ];
        }

        return null;
    }
}
