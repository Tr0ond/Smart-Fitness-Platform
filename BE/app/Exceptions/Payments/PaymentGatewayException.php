<?php

namespace App\Exceptions\Payments;

use RuntimeException;
use Throwable;

class PaymentGatewayException extends RuntimeException
{
    public function __construct(
        public readonly string $safeCode,
        public readonly bool $retryable,
        public readonly int $responseStatus = 503,
        ?Throwable $previous = null,
    ) {
        parent::__construct('Không thể tạo liên kết thanh toán lúc này.', 0, $previous);
    }
}
