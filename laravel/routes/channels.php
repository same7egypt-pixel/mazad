<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('auctions.{auction}', function (\App\Models\User $user, \App\Models\Auction $auction): bool {
    return $user->country_id === $auction->country_id && $user->status === 'active';
});
