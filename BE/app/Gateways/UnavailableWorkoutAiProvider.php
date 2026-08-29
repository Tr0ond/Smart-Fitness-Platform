<?php

namespace App\Gateways;

use App\Contracts\Ai\WorkoutAiProvider;
use App\Data\Ai\WorkoutAiResult;
use App\Exceptions\Ai\AiProviderException;

class UnavailableWorkoutAiProvider implements WorkoutAiProvider
{
    public function providerName(): string
    {
        return (string) config('ai.provider', 'unavailable');
    }

    public function modelName(): string
    {
        return (string) config('ai.model', 'not-configured');
    }

    /** @param array<string, mixed> $context */
    public function generateStructuredProposal(array $context): WorkoutAiResult
    {
        throw new AiProviderException(
            'Nhà cung cấp AI chưa được cấu hình.',
            503,
            'AI_PROVIDER_NOT_CONFIGURED',
            'THAT_BAI',
        );
    }
}
