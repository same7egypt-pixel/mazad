<?php

namespace App\Domain\Payments\Services;

use App\Domain\Core\Money\Decimal;
use App\Models\Order;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CreditSellerWallet
{
    public function handle(Order $order): WalletTransaction
    {
        if ($order->status !== 'paid' && ! ($order->payment_method === 'cash_on_delivery' && $order->collection_status === 'collected')) {
            throw ValidationException::withMessages(['order' => 'Seller earnings can only be credited after payment succeeds.']);
        }

        $wallet = $this->lockWallet($order);
        $referenceType = Order::class;
        $existing = WalletTransaction::query()
            ->where('wallet_id', $wallet->id)
            ->where('type', 'sale_earning')
            ->where('reference_type', $referenceType)
            ->where('reference_id', $order->id)
            ->lockForUpdate()
            ->first();

        if ($existing !== null) {
            return $existing;
        }

        $amount = (string) $order->seller_amount;
        $transaction = WalletTransaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => 'sale_earning',
            'amount' => $amount,
            'reference_type' => $referenceType,
            'reference_id' => $order->id,
            'status' => 'pending',
            'metadata' => ['order_id' => $order->id, 'policy_event' => $order->payment_method === 'cash_on_delivery' ? 'cash_collected' : 'payment_succeeded'],
        ]);

        $wallet->update(['pending_balance' => Decimal::add((string) $wallet->pending_balance, $amount)]);

        return $transaction;
    }

    private function lockWallet(Order $order): Wallet
    {
        $wallet = Wallet::query()->where('user_id', $order->seller_id)->where('currency_id', $order->currency_id)->lockForUpdate()->first();
        if ($wallet !== null) {
            return $wallet;
        }

        try {
            Wallet::query()->create([
                'user_id' => $order->seller_id,
                'currency_id' => $order->currency_id,
                'available_balance' => '0.00',
                'pending_balance' => '0.00',
            ]);
        } catch (QueryException) {
            // A concurrent payment may have created the unique seller/currency wallet first.
        }

        return Wallet::query()->where('user_id', $order->seller_id)->where('currency_id', $order->currency_id)->lockForUpdate()->firstOrFail();
    }
}
