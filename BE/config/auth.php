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
    'register_rate_limit_per_minute' => (int) env('AUTH_REGISTER_RATE_LIMIT_PER_MINUTE', 5),
    'forgot_password_rate_limit_per_minute' => (int) env('AUTH_FORGOT_PASSWORD_RATE_LIMIT_PER_MINUTE', 5),
    'reset_password_rate_limit_per_minute' => (int) env('AUTH_RESET_PASSWORD_RATE_LIMIT_PER_MINUTE', 10),
    'password_reset_lifetime_minutes' => (int) env('AUTH_PASSWORD_RESET_LIFETIME_MINUTES', 30),
    'password_reset_url' => env('AUTH_PASSWORD_RESET_URL', 'http://localhost:5173/dat-lai-mat-khau'),
];
