<?php

namespace App\Exceptions\Payments;

use RuntimeException;

class InvalidWebhookSignatureException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Chữ ký webhook không hợp lệ.');
    }
}
