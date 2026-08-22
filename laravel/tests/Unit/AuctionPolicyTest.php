<?php

namespace Tests\Unit;

use App\Models\Auction;
use App\Models\Product;
use App\Models\User;
use App\Policies\AuctionPolicy;
use Tests\TestCase;

class AuctionPolicyTest extends TestCase
{
    public function test_seller_cannot_bid_on_their_own_country_auction(): void
    {
        $seller = new User(['country_id' => 1, 'status' => 'active', 'verification_status' => 'verified']);
        $seller->setAttribute('id', 7);
        $auction = new Auction(['country_id' => 1]);
        $auction->setRelation('product', new Product(['user_id' => 7]));

        self::assertFalse((new AuctionPolicy())->bid($seller, $auction));
    }

    public function test_unverified_user_cannot_bid_even_when_they_match_the_country(): void
    {
        $user = new User(['country_id' => 1, 'status' => 'active', 'verification_status' => 'unverified']);
        $user->setAttribute('id', 8);
        $auction = new Auction(['country_id' => 1]);
        $auction->setRelation('product', new Product(['user_id' => 7]));

        self::assertFalse((new AuctionPolicy())->bid($user, $auction));
    }
}
