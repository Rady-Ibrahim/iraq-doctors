<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WasenderAPI — WhatsApp OTP delivery
    |--------------------------------------------------------------------------
    |
    | Create a session at https://wasenderapi.com, connect WhatsApp via QR,
    | then paste the session API key below.
    |
    */

    'api_key' => env('WASENDER_API_KEY', ''),

    'base_url' => rtrim((string) env('WASENDER_BASE_URL', 'https://www.wasenderapi.com'), '/'),

    'timeout' => (int) env('WASENDER_TIMEOUT', 15),

    /*
    | When true (and not production), OTP is logged instead of sent if api_key
    | is empty — useful for local/Postman without WhatsApp.
    */
    'log_fallback' => (bool) env('WASENDER_LOG_FALLBACK', true),

];
