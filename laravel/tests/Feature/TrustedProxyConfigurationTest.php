<?php

namespace Tests\Feature;

use Illuminate\Http\Middleware\TrustProxies;
use Illuminate\Http\Request;
use Tests\TestCase;

class TrustedProxyConfigurationTest extends TestCase
{
    public function test_trusted_reverse_proxy_makes_forwarded_https_request_secure(): void
    {
        TrustProxies::at('*');

        try {
            $request = Request::create('http://auction-api.internal/up', 'GET', server: [
                'REMOTE_ADDR' => '10.0.0.10',
                'HTTP_HOST' => 'auction-api.internal',
                'HTTP_X_FORWARDED_PROTO' => 'https',
                'HTTP_X_FORWARDED_HOST' => 'api.marketplace.example',
            ]);

            $response = app(TrustProxies::class)->handle($request, fn (Request $trustedRequest) => response()->json([
                'secure' => $trustedRequest->isSecure(),
                'scheme' => $trustedRequest->getScheme(),
                'host' => $trustedRequest->getHost(),
            ]));

            self::assertTrue($response->getData(true)['secure']);
            self::assertSame('https', $response->getData(true)['scheme']);
            self::assertSame('api.marketplace.example', $response->getData(true)['host']);
        } finally {
            TrustProxies::flushState();
        }
    }
}
