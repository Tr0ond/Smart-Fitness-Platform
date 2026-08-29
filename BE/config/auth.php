<?php

use App\Models\NguoiDung;

return [
    'defaults' => [
        'guard' => 'api',
        'passwords' => null,
    ],

    'guards' => [
        'api' => [
            'driver' => 'access-token',
            'provider' => 'nguoi_dung',
        ],
    ],

    'providers' => [
        'nguoi_dung' => [
            'driver' => 'eloquent',
            'model' => NguoiDung::class,
        ],
    ],

    'passwords' => [],

    // Implementation policies, independent from Membership entitlement.
    'access_token_lifetime_minutes' => (int) env('AUTH_TOKEN_LIFETIME_MINUTES', 43200),
    'login_rate_limit_per_minute' => (int) env('AUTH_LOGIN_RATE_LIMIT_PER_MINUTE', 5),
];
