<?php

namespace App\Filament\Concerns;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait ScopesMarketplaceData
{
    protected function actingAdmin(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    protected function isGlobalAdmin(): bool
    {
        return $this->actingAdmin()?->hasRole('GLOBAL_SUPER_ADMIN') ?? false;
    }

    protected function scopeToAdminCountry(Builder $query, string $column = 'country_id'): Builder
    {
        $user = $this->actingAdmin();

        if (! $user || $this->isGlobalAdmin() || ! $user->country_id) {
            return $query;
        }

        return $query->where($column, $user->country_id);
    }

    protected function scopedCountryId(): ?int
    {
        $user = $this->actingAdmin();

        if (! $user || $this->isGlobalAdmin()) {
            return null;
        }

        return $user->country_id;
    }
}
