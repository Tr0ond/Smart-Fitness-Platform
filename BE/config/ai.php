<?php

return [
    'provider' => env('AI_PROVIDER', 'unavailable'),
    'model' => env('AI_MODEL', 'gemini-3.1-flash-lite'),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),
    'max_output_tokens' => (int) env('AI_MAX_OUTPUT_TOKENS', 8192),
    'proposal_ttl_hours' => (int) env('AI_PROPOSAL_TTL_HOURS', 24),
    'idempotency_ttl_hours' => (int) env('AI_IDEMPOTENCY_TTL_HOURS', 24),
    'candidate_limit' => (int) env('AI_CANDIDATE_LIMIT', 200),
    'gemini' => [
        'api_key' => env('GEMINI_API_KEY'),
        'base_url' => env('GEMINI_BASE_URL', 'https://generativelanguage.googleapis.com'),
    ],
];
