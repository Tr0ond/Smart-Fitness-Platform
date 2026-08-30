<?php

namespace App\Exceptions\Chat;

use RuntimeException;

class PtChatWorkflowException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $responseStatus,
        public readonly string $safeCode,
    ) {
        parent::__construct($message);
    }
}
