<?php

namespace App\Console\Commands;

use App\Notifications\SubscriptionExpiringSoonAdmin;
use App\Notifications\SubscriptionExpiryReminder;
use App\Services\AdminNotificationService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Modules\Laboratory\Models\LaboratorySubscription;
use Modules\Pharmacy\Models\PharmacySubscription;
use Modules\Subscription\Models\DoctorSubscription;

class ProcessSubscriptionsCommand extends Command
{
    protected $signature = 'subscriptions:process';

    protected $description = 'Expire ended subscriptions and send 3-day expiry reminders';

    public function handle(): int
    {
        $expired = DoctorSubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'expired']);

        $expiredLabs = LaboratorySubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'expired']);

        $expiredPharmacies = PharmacySubscription::where('status', 'active')
            ->whereNotNull('end_date')
            ->whereDate('end_date', '<', today())
            ->update(['status' => 'expired']);

        $this->info("Marked {$expired} doctor, {$expiredLabs} laboratory, and {$expiredPharmacies} pharmacy subscription(s) as expired.");

        $reminderDate = today()->addDays(3);

        $dueForReminder = DoctorSubscription::with(['doctor.user', 'subscription'])
            ->where('status', 'active')
            ->whereDate('end_date', $reminderDate)
            ->whereNull('expiry_reminder_sent_at')
            ->get();

        $sent = 0;
        foreach ($dueForReminder as $sub) {
            $doctorUser = $sub->doctor?->user;
            $email = $doctorUser?->email;
            $planName = $sub->subscription?->name ?? 'اشتراكك';
            $endDate = $sub->end_date?->format('Y-m-d');

            if ($email) {
                try {
                    Mail::raw(
                        "تنبيه: اشتراكك في باقة \"{$planName}\" سينتهي بتاريخ {$endDate}. يرجى تجديد الاشتراك من لوحة تحكم الطبيب.",
                        fn ($message) => $message->to($email)->subject('تنبيه: اشتراكك على وشك الانتهاء — أطباء العراق')
                    );
                } catch (\Throwable $e) {
                    $this->warn("Failed to email {$email}: {$e->getMessage()}");
                }
            }

            if ($doctorUser) {
                $doctorUser->notify(new SubscriptionExpiryReminder($sub));
            }

            AdminNotificationService::notify(new SubscriptionExpiringSoonAdmin($sub));

            $sub->update(['expiry_reminder_sent_at' => now()]);
            $sent++;
        }

        $this->info("Sent {$sent} doctor expiry reminder(s).");

        $labReminderDate = today()->addDays(3);
        $labDue = LaboratorySubscription::with(['laboratory.user', 'subscription'])
            ->where('status', 'active')
            ->whereDate('end_date', $labReminderDate)
            ->whereNull('expiry_reminder_sent_at')
            ->get();

        $labSent = 0;
        foreach ($labDue as $sub) {
            $email = $sub->laboratory?->user?->email;
            $planName = $sub->subscription?->name ?? 'اشتراكك';
            $endDate = $sub->end_date?->format('Y-m-d');

            if ($email) {
                try {
                    Mail::raw(
                        "تنبيه: اشتراك مختبرك في باقة \"{$planName}\" سينتهي بتاريخ {$endDate}. يرجى التجديد من لوحة تحكم المختبر.",
                        fn ($message) => $message->to($email)->subject('تنبيه: اشتراك المختبر على وشك الانتهاء — أطباء العراق')
                    );
                } catch (\Throwable $e) {
                    $this->warn("Failed to email {$email}: {$e->getMessage()}");
                }
            }

            $sub->update(['expiry_reminder_sent_at' => now()]);
            $labSent++;
        }

        $this->info("Sent {$labSent} laboratory expiry reminder(s).");

        $pharmacyReminderDate = today()->addDays(3);
        $pharmacyDue = PharmacySubscription::with(['pharmacy.user', 'subscription'])
            ->where('status', 'active')
            ->whereDate('end_date', $pharmacyReminderDate)
            ->whereNull('expiry_reminder_sent_at')
            ->get();

        $pharmacySent = 0;
        foreach ($pharmacyDue as $sub) {
            $email = $sub->pharmacy?->user?->email;
            $planName = $sub->subscription?->name ?? 'اشتراكك';
            $endDate = $sub->end_date?->format('Y-m-d');

            if ($email) {
                try {
                    Mail::raw(
                        "تنبيه: اشتراك صيدليتك في باقة \"{$planName}\" سينتهي بتاريخ {$endDate}. يرجى التجديد من لوحة تحكم الصيدلية.",
                        fn ($message) => $message->to($email)->subject('تنبيه: اشتراك الصيدلية على وشك الانتهاء — أطباء العراق')
                    );
                } catch (\Throwable $e) {
                    $this->warn("Failed to email {$email}: {$e->getMessage()}");
                }
            }

            $sub->update(['expiry_reminder_sent_at' => now()]);
            $pharmacySent++;
        }

        $this->info("Sent {$pharmacySent} pharmacy expiry reminder(s).");

        return self::SUCCESS;
    }
}
