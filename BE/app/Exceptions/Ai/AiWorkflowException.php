<?php

namespace App\Exceptions\Ai;

use RuntimeException;

class AiWorkflowException extends RuntimeException
{
    /** @param array<string, mixed>|null $safeStructuredOutput */
    public function __construct(
        string $message,
        public readonly int $responseStatus,
        public readonly string $safeCode,
        public readonly string $modelCallStatus = 'LOI_NGHIEP_VU',
        public readonly ?array $safeStructuredOutput = null,
    ) {
        parent::__construct($message);
    }
}
