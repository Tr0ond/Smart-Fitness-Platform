<?php

namespace App\Exceptions\Catalog;

use RuntimeException;

class CatalogWorkflowException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $responseStatus,
        public readonly string $safeCode,
    ) {
        parent::__construct($message);
    }
}
