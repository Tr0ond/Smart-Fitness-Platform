<?php

return [
    'outbox_retry_batch_size' => (int) env('PT_CHAT_OUTBOX_RETRY_BATCH_SIZE', 100),
    'outbox_retry_base_seconds' => (int) env('PT_CHAT_OUTBOX_RETRY_BASE_SECONDS', 15),
    'outbox_retry_max_seconds' => (int) env('PT_CHAT_OUTBOX_RETRY_MAX_SECONDS', 3600),
    'outbox_advisory_lock_timeout_seconds' => (int) env('PT_CHAT_OUTBOX_LOCK_TIMEOUT_SECONDS', 5),
];
