<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function update(User $user, Product $product): bool
    {
        return $user->can('products.update') && $user->id === $product->user_id && $user->country_id === $product->country_id;
    }

    public function approve(User $user, Product $product): bool
    {
        return $user->can('products.approve') && ($user->hasRole('GLOBAL_SUPER_ADMIN') || $user->country_id === $product->country_id);
    }
}
