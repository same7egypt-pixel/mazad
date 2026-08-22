<?php

namespace App\Policies;

use App\Models\Auction;
use App\Models\User;

class AuctionPolicy
{
    public function create(User $user): bool
    {
        return $user->status === 'active' && $user->can('auctions.create');
    }

    public function bid(User $user, Auction $auction): bool
    {
        return $user->country_id === $auction->country_id
            && $auction->product->user_id !== $user->id
            && $user->status === 'active'
            && $user->verification_status === 'verified'
            && $user->can('auctions.bid');
    }

    public function manage(User $user, Auction $auction): bool
    {
        return $user->can('auctions.manage') && ($user->hasRole('GLOBAL_SUPER_ADMIN') || $user->country_id === $auction->country_id);
    }

    public function cancel(User $user, Auction $auction): bool
    {
        return ($auction->product->user_id === $user->id && $user->can('auctions.create')) || $this->manage($user, $auction);
    }
}
