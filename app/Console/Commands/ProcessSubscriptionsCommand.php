<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
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

        $this->info("Marked {$expired} subscription(s) as expired.");

        $reminderDate = today()->addDays(3);

        $dueForReminder = DoctorSubscription::with(['doctor.user', 'subscription'])
            ->where('status', 'active')
            ->whereDate('end_date', $reminderDate)
            ->whereNull('expiry_reminder_sent_at')
            ->get();

        $sent = 0;
        foreach ($dueForReminder as $sub) {
            $email = $sub->doctor?->user?->email;
            if (!$email) {
                continue;
            }

            $planName = $sub->subscription?->name ?? 'اشتراكك';
            $endDate = $sub->end_date?->format('Y-m-d');

            try {
                Mail::raw(
                    "تنبيه: اشتراكك في باقة \"{$planName}\" سينتهي بتاريخ {$endDate}. يرجى تجديد الاشتراك من لوحة تحكم الطبيب.",
                    fn ($message) => $message->to($email)->subject('تنبيه: اشتراكك على وشك الانتهاء — أطباء العراق')
                );
                $sub->update(['expiry_reminder_sent_at' => now()]);
                $sent++;
            } catch (\Throwable $e) {
                $this->warn("Failed to email {$email}: {$e->getMessage()}");
            }
        }

        $this->info("Sent {$sent} expiry reminder(s).");

        return self::SUCCESS;
    }
}
