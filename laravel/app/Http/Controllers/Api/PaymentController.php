<?php

namespace App\Http\Controllers\Api;

use App\Domain\Core\Context\MarketplaceContext;
use App\Domain\Payments\Services\InitiatePayment;
use App\Domain\Payments\Services\PaymentGatewayResolver;
use App\Domain\Payments\Services\ProcessPaymentWebhook;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function initiate(Order $order, Request $request, MarketplaceContext $context, InitiatePayment $service): JsonResponse
    {
        $context->assertMatches($order->country_id);
        $payment = $service->handle($order->id, $request->user());

        return response()->json(['payment' => $payment], 201);
    }

    public function webhook(string $gateway, Request $request, PaymentGatewayResolver $resolver, ProcessPaymentWebhook $service): JsonResponse
    {
        $verifiedWebhook = $resolver->forWebhook($gateway)->verifyWebhook($request->getContent(), $request->headers->all());
        $payment = $service->handle($gateway, $verifiedWebhook);

        return response()->json(['payment_id' => $payment->id, 'status' => $payment->status]);
    }
}
