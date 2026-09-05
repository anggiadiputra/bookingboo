<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cloudflare Turnstile
    |--------------------------------------------------------------------------
    |
    | Site key dan secret key dari Cloudflare Turnstile dashboard.
    | Untuk development, gunakan test keys:
    |   Site key  : 1x00000000000000000000AA
    |   Secret key: 1x0000000000000000000000000000000AA
    |
    */

    'enabled' => env('TURNSTILE_ENABLED', true),

    'site_key' => env('TURNSTILE_SITE_KEY', '1x00000000000000000000AA'),

    'secret_key' => env('TURNSTILE_SECRET_KEY', '1x0000000000000000000000000000000AA'),
];
