<?php

namespace App\Exceptions\Payments;

use RuntimeException;

class PaymentWorkflowException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $responseStatus,
        public readonly string $safeCode,
    ) {
        parent::__construct($message);
    }
}
