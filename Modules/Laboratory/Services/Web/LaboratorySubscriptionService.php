<?php

namespace Modules\Laboratory\Services\Web;

use App\Notifications\LaboratorySubscriptionRequested;
use App\Services\AdminNotificationService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Laboratory\Models\Laboratory;
use Modules\Laboratory\Models\LaboratorySubscription;
use Modules\Subscription\Models\Subscription;

class LaboratorySubscriptionService
{
    public function submitPaymentRequest(
        int $laboratoryId,
        int $subscriptionId,
        float $submittedAmount,
        UploadedFile $receipt,
        string $paymentMethod = 'bank_transfer'
    ): LaboratorySubscription {
        return DB::transaction(function () use ($laboratoryId, $subscriptionId, $submittedAmount, $receipt, $paymentMethod) {
            $laboratory = Laboratory::findOrFail($laboratoryId);
            $subscription = Subscription::ofType('laboratory')->findOrFail($subscriptionId);

            if ($laboratory->laboratorySubscriptions()->active()->exists()) {
                throw new \InvalidArgumentException('لديك اشتراك نشط بالفعل');
            }

            if ($laboratory->laboratorySubscriptions()->pendingPayment()->exists()) {
                throw new \InvalidArgumentException('لديك طلب دفع قيد المراجعة بالفعل');
            }

            if ((float) $submittedAmount !== (float) $subscription->price) {
                throw new \InvalidArgumentException('المبلغ المُدخل يجب أن يساوي سعر الباقة بالضبط');
            }

            $labSubscription = LaboratorySubscription::create([
                'laboratory_id' => $laboratoryId,
                'subscription_id' => $subscriptionId,
                'status' => 'pending_payment',
                'amount_paid' => 0,
                'submitted_amount' => $submittedAmount,
                'payment_method' => $paymentMethod,
                'payment_receipt' => $receipt->store('subscriptions/receipts', 'public'),
            ]);

            AdminNotificationService::notify(new LaboratorySubscriptionRequested($labSubscription));

            return $labSubscription;
        });
    }

    public function confirmPayment(int $laboratorySubscriptionId, int $adminUserId): LaboratorySubscription
    {
        return DB::transaction(function () use ($laboratorySubscriptionId, $adminUserId) {
            $labSubscription = LaboratorySubscription::with('subscription')
                ->where('status', 'pending_payment')
                ->findOrFail($laboratorySubscriptionId);

            $subscription = $labSubscription->subscription;
            $startDate = now();
            $endDate = now()->addDays($subscription->duration_days);

            $labSubscription->update([
                'status' => 'active',
                'start_date' => $startDate,
                'end_date' => $endDate,
                'amount_paid' => $labSubscription->submitted_amount ?? $subscription->price,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
                'payment_reject_reason' => null,
                'expiry_reminder_sent_at' => null,
            ]);

            $labSubscription->laboratory->update(['subscription_id' => $subscription->id]);

            return $labSubscription->fresh(['laboratory.user', 'subscription']);
        });
    }

    public function rejectPayment(int $laboratorySubscriptionId, int $adminUserId, ?string $reason = null): LaboratorySubscription
    {
        return DB::transaction(function () use ($laboratorySubscriptionId, $adminUserId, $reason) {
            $labSubscription = LaboratorySubscription::where('status', 'pending_payment')
                ->findOrFail($laboratorySubscriptionId);

            $labSubscription->update([
                'status' => 'cancelled',
                'payment_reject_reason' => $reason,
                'reviewed_by' => $adminUserId,
                'reviewed_at' => now(),
                'cancelled_at' => now(),
            ]);

            return $labSubscription->fresh(['laboratory.user', 'subscription']);
        });
    }

    public function getSubscriptionStatus(int $laboratoryId): ?array
    {
        $laboratory = Laboratory::findOrFail($laboratoryId);

        $pending = $laboratory->laboratorySubscriptions()->pendingPayment()->with('subscription')->latest()->first();
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

        $active = $laboratory->laboratorySubscriptions()->active()->with('subscription')->first();
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
