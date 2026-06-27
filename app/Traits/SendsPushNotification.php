<?php

namespace App\Traits;

use App\Notifications\Channels\OneSignalChannel;
use App\Services\OneSignalService;

/**
 * OneSignal push — patient mobile app only (database + push).
 */
trait SendsPushNotification
{
    /** @return array<int, string|class-string> */
    protected function patientPushChannels(): array
    {
        $channels = ['database'];

        if (OneSignalService::isEnabled()) {
            $channels[] = OneSignalChannel::class;
        }

        return $channels;
    }
}
