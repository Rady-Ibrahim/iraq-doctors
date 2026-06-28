<?php

return [

    /*
    |--------------------------------------------------------------------------
    | OTP test / development
    |--------------------------------------------------------------------------
    |
    | When true (never on production), send-otp may include the code in the JSON
    | response so Postman / local testing works without real SMS.
    |
    */
    'expose_code_in_response' => (bool) env('OTP_EXPOSE_CODE_IN_RESPONSE', false),

    /** Minutes before OTP expires */
    'expires_minutes' => (int) env('OTP_EXPIRES_MINUTES', 10),

    /** Max wrong attempts per OTP */
    'max_attempts' => 3,

];
