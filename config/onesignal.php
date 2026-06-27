<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OneSignal — patient mobile app push notifications only
    |--------------------------------------------------------------------------
    | Doctors/admins receive in-app (database) notifications on the web dashboard.
    */

    'enabled' => env('ONESIGNAL_ENABLED', false),

    'app_id' => env('ONESIGNAL_APP_ID'),

    'rest_api_key' => env('ONESIGNAL_REST_API_KEY'),

    'api_url' => env('ONESIGNAL_API_URL', 'https://api.onesignal.com/notifications'),

];
