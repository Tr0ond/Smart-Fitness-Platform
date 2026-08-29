<?php

return [
    'client_id' => env('PAYOS_CLIENT_ID'),
    'api_key' => env('PAYOS_API_KEY'),
    'checksum_key' => env('PAYOS_CHECKSUM_KEY'),
    'return_url' => env('PAYOS_RETURN_URL'),
    'cancel_url' => env('PAYOS_CANCEL_URL'),
    'base_url' => env('PAYOS_BASE_URL', 'https://api-merchant.payos.vn'),
    'timeout_seconds' => (float) env('PAYOS_TIMEOUT_SECONDS', 10),
    'order_ttl_minutes' => (int) env('PAYOS_ORDER_TTL_MINUTES', 30),
    'idempotency_ttl_hours' => (int) env('PAYOS_IDEMPOTENCY_TTL_HOURS', 24),
    'channel_code' => 'PAYOS',
];
