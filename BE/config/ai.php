<?php

return [
    'provider' => env('AI_PROVIDER', 'unavailable'),
    'model' => env('AI_MODEL', 'not-configured'),
    'timeout_seconds' => (int) env('AI_TIMEOUT_SECONDS', 30),
    'proposal_ttl_hours' => (int) env('AI_PROPOSAL_TTL_HOURS', 24),
    'idempotency_ttl_hours' => (int) env('AI_IDEMPOTENCY_TTL_HOURS', 24),
    'candidate_limit' => (int) env('AI_CANDIDATE_LIMIT', 200),
];
