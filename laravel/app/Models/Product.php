<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use Searchable;

    protected $fillable = ['user_id', 'country_id', 'city_id', 'category_id', 'currency_id', 'title', 'description', 'condition', 'status', 'approved_at', 'approved_by'];

    protected function casts(): array
    {
        return ['approved_at' => 'datetime'];
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function city(): BelongsTo
    {
        return $this->belongsTo(City::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class);
    }

    public function auction(): HasOne
    {
        return $this->hasOne(Auction::class);
    }

    public function shouldBeSearchable(): bool
    {
        return $this->status === 'active';
    }

    public function toSearchableArray(): array
    {
        return $this->only(['id', 'country_id', 'city_id', 'category_id', 'currency_id', 'title', 'description', 'condition', 'status']);
    }
}
