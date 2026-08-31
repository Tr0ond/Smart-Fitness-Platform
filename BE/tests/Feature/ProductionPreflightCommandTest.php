<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class ProductionPreflightCommandTest extends TestCase
{
    public function test_preflight_fails_closed_without_exposing_configuration_secrets(): void
    {
        config([
            'app.debug' => true,
            'queue.default' => 'sync',
            'ai.provider' => 'gemini',
            'ai.model' => 'gemini-test',
            'ai.gemini.api_key' => 'secret-must-not-be-printed',
            'ai.gemini.base_url' => 'https://gemini.example.test',
            'broadcasting.default' => 'log',
        ]);

        $this->assertSame(1, Artisan::call('smart-fitness:preflight', ['--no-network' => true]));
        $output = Artisan::output();
        $this->assertStringContainsString('[FAIL] APP_ENV=production', $output);
        $this->assertStringContainsString('[FAIL] Queue', $output);
        $this->assertStringContainsString('PREFLIGHT_FAILED', $output);
        $this->assertStringNotContainsString('secret-must-not-be-printed', $output);
    }

    public function test_preflight_rejects_ambiguous_network_flags(): void
    {
        $this->assertSame(1, Artisan::call('smart-fitness:preflight', [
            '--no-network' => true,
            '--external' => true,
        ]));
        $this->assertStringContainsString('Chỉ chọn một trong --no-network hoặc --external', Artisan::output());
    }
}
