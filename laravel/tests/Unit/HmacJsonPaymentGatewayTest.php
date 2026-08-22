<?php

namespace Tests\Unit;

use App\Domain\Payments\Gateways\HmacJsonPaymentGateway;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class HmacJsonPaymentGatewayTest extends TestCase
{
    public function test_it_verifies_a_signed_generic_hmac_json_webhook(): void
    {
        config()->set('marketplace.hmac_webhook', [
            'gateway_name' => 'generic-hmac',
            'secret' => 'test-hmac-secret',
            'signature_header' => 'x-payment-signature',
        ]);
        $payload = json_encode([
            'payment_id' => 12,
            'transaction_id' => 'txn-12',
            'status' => 'succeeded',
            'amount' => '100.00',
            'currency' => 'SAR',
        ], JSON_THROW_ON_ERROR);

        $webhook = app(HmacJsonPaymentGateway::class)->verifyWebhook($payload, [
            'X-Payment-Signature' => [hash_hmac('sha256', $payload, 'test-hmac-secret')],
        ]);

        self::assertSame(12, $webhook->paymentId);
        self::assertSame('txn-12', $webhook->externalTransactionId);
        self::assertSame('succeeded', $webhook->status);
    }

    public function test_it_rejects_a_webhook_with_an_invalid_signature_before_processing_payload(): void
    {
        config()->set('marketplace.hmac_webhook.secret', 'test-hmac-secret');
        $payload = '{"payment_id":12,"transaction_id":"txn-12","status":"succeeded","amount":"100.00","currency":"SAR"}';

        $this->expectException(ValidationException::class);
        app(HmacJsonPaymentGateway::class)->verifyWebhook($payload, [
            'x-payment-signature' => ['not-a-valid-signature'],
        ]);
    }
}
