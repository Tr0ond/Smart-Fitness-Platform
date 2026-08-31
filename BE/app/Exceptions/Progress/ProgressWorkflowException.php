<?php

namespace App\Exceptions\Progress;

use RuntimeException;

class ProgressWorkflowException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $responseStatus,
        public readonly string $safeCode,
    ) {
        parent::__construct($message);
    }
}
