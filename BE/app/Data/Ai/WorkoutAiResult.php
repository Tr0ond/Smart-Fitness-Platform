<?php

namespace App\Data\Ai;

final readonly class WorkoutAiResult
{
    /**
     * @param  array<string, mixed>  $structuredOutput
     */
    public function __construct(
        public array $structuredOutput,
        public ?string $providerRequestId = null,
        public ?int $inputUnits = null,
        public ?int $outputUnits = null,
    ) {}
}
