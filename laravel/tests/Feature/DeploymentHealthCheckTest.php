<?php

namespace Tests\Feature;

use Tests\TestCase;

class DeploymentHealthCheckTest extends TestCase
{
    public function test_render_health_check_endpoint_is_available(): void
    {
        $this->get('/up')->assertOk();
    }
}
