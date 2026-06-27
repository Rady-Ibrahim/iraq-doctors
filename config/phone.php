<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default country for ambiguous local numbers (without + prefix)
    |--------------------------------------------------------------------------
    | 964 = Iraq, 20 = Egypt (useful for Firebase SMS testing)
    */
    'default_country_code' => env('PHONE_DEFAULT_COUNTRY', '964'),

    /** @var array<int, string> */
    'supported_country_codes' => array_values(array_filter(array_map(
        'trim',
        explode(',', env('PHONE_SUPPORTED_COUNTRIES', '964,20'))
    ))),

];
