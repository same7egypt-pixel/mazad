<?php

namespace App\Domain\Payments\Services;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InitiatePayment
{
    public function handle(int $orderId, User $buyer): Payment
    {
        [$payment, $gateway] = DB::transaction(function () use ($orderId, $buyer): array {
            $order = Order::query()->with('country.currency')->lockForUpdate()->findOrFail($orderId);

            if ($order->buyer_id !== $buyer->id || $order->country_id !== $buyer->country_id) {
                throw ValidationException::withMessages(['order' => 'This order is not available for payment.']);
            }
            if ($order->status !== 'waiting_payment') {
                throw ValidationException::withMessages(['order' => 'This order is not awaiting payment.']);
            }

            $existing = Payment::query()->where('order_id', $order->id)->whereIn('status', ['initiating', 'pending'])->lockForUpdate()->first();
            if ($existing !== null) {
                return [$existing, null];
            }

            $gateway = app(PaymentGatewayResolver::class)->forCountry($order->country);
            $payment = Payment::query()->create([
                'order_id' => $order->id,
                'country_id' => $order->country_id,
                'gateway' => $gateway->name(),
                'amount' => $order->amount,
                'currency' => $order->currency->code,
                'status' => 'initiating',
                'payload' => [],
            ]);

            return [$payment, $gateway];
        }, 3);

        if ($gateway === null) {
            return $payment;
        }

        $order = Order::query()->findOrFail($payment->order_id);
        $checkout = $gateway->initiate($order, $payment->id);

        return DB::transaction(function () use ($payment, $checkout): Payment {
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            if ($lockedPayment->status !== 'initiating') {
                return $lockedPayment;
            }

            $lockedPayment->update([
                'transaction_id' => $checkout->externalTransactionId,
                'status' => 'pending',
                'payload' => ['checkout_url' => $checkout->checkoutUrl, 'expires_at' => $checkout->expiresAt?->toIso8601String()],
            ]);

            return $lockedPayment->fresh();
        }, 3);
    }
}
