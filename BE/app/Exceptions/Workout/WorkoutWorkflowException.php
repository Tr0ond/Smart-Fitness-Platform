<?php

namespace App\Exceptions\Workout;

use RuntimeException;

class WorkoutWorkflowException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly int $responseStatus,
        public readonly string $safeCode,
    ) {
        parent::__construct($message);
    }
}
