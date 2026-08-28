<?php

namespace Tests\Feature;

use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * Trang skeleton chạy được mà không yêu cầu database nghiệp vụ.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Smart Fitness Backend');
    }

    /** Kiểm tra endpoint health mặc định của Laravel. */
    public function test_health_endpoint_returns_a_successful_response(): void
    {
        $this->get('/up')->assertOk();
    }
}
