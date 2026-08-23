<?php

namespace Tests\Feature;

use Tests\TestCase;

class CorsConfigurationTest extends TestCase
{
    public function test_the_netlify_trial_origin_is_included_in_the_default_cors_policy(): void
    {
        $this->assertContains('https://mazad-marketplace.netlify.app', config('cors.allowed_origins'));
    }

    public function test_configured_frontend_origin_receives_cors_preflight_headers(): void
    {
        config([
            'cors.allowed_origins' => ['https://staging.marketplace.example'],
            'cors.supports_credentials' => true,
        ]);

        $this->withServerVariables([
            'HTTP_ORIGIN' => 'https://staging.marketplace.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
            'HTTP_ACCESS_CONTROL_REQUEST_HEADERS' => 'authorization,content-type,x-marketplace-country',
        ])->options('/api/auth/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://staging.marketplace.example')
            ->assertHeader('Access-Control-Allow-Credentials', 'true');
    }

    public function test_unconfigured_origin_is_not_reflected_as_an_allowed_origin(): void
    {
        config(['cors.allowed_origins' => ['https://staging.marketplace.example']]);

        $this->withServerVariables([
            'HTTP_ORIGIN' => 'https://untrusted.example',
            'HTTP_ACCESS_CONTROL_REQUEST_METHOD' => 'POST',
        ])->options('/api/auth/login')
            ->assertNoContent()
            ->assertHeader('Access-Control-Allow-Origin', 'https://staging.marketplace.example');
    }
}
