<?php

namespace App\Domain\Auctions\Services;

use App\Domain\Core\Context\MarketplaceContext;
use App\Domain\Core\Money\Decimal;
use App\Models\Auction;
use App\Models\Product;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CreateAuction
{
    public function handle(User $seller, array $attributes, MarketplaceContext $context): Auction
    {
        return DB::transaction(function () use ($seller, $attributes, $context): Auction {
            $product = Product::query()->lockForUpdate()->findOrFail($attributes['product_id']);

            if ($product->user_id !== $seller->id || $product->country_id !== $context->id()) {
                throw ValidationException::withMessages(['product_id' => 'The selected product is not available for this marketplace.']);
            }
            if ($product->status !== 'approved') {
                throw ValidationException::withMessages(['product_id' => 'Only approved products can be scheduled for auction.']);
            }
            if ($product->currency_id !== $context->country()->currency_id) {
                throw ValidationException::withMessages(['product_id' => 'The product currency does not match the marketplace currency.']);
            }
            if (Auction::query()->where('product_id', $product->id)->exists()) {
                throw ValidationException::withMessages(['product_id' => 'This product is already assigned to an auction.']);
            }
            if (isset($attributes['reserve_price']) && Decimal::compare((string) $attributes['reserve_price'], (string) $attributes['starting_price']) < 0) {
                throw ValidationException::withMessages(['reserve_price' => 'The reserve price cannot be lower than the starting price.']);
            }

            $startTime = CarbonImmutable::parse($attributes['start_time']);
            $endTime = CarbonImmutable::parse($attributes['end_time']);
            if ($endTime->lte($startTime)) {
                throw ValidationException::withMessages(['end_time' => 'The auction must end after it starts.']);
            }

            $isLive = $startTime->lte(now());
            $auction = Auction::query()->create([
                'product_id' => $product->id,
                'country_id' => $context->id(),
                'currency_id' => $product->currency_id,
                'starting_price' => $attributes['starting_price'],
                'current_price' => $attributes['starting_price'],
                'reserve_price' => $attributes['reserve_price'] ?? null,
                'minimum_increment' => $attributes['minimum_increment'],
                'start_time' => $startTime,
                'end_time' => $endTime,
                'status' => $isLive ? 'live' : 'upcoming',
            ]);

            if ($isLive) {
                $product->update(['status' => 'active']);
            }

            return $auction->fresh(['product.media', 'currency']);
        }, 3);
    }
}
