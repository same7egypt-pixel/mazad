<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Auction extends Model
{
    protected $fillable = ['product_id', 'country_id', 'currency_id', 'starting_price', 'current_price', 'reserve_price', 'minimum_increment', 'start_time', 'end_time', 'status', 'winner_id', 'bid_count', 'version'];
    protected function casts(): array { return ['starting_price' => 'decimal:2', 'current_price' => 'decimal:2', 'reserve_price' => 'decimal:2', 'minimum_increment' => 'decimal:2', 'start_time' => 'datetime', 'end_time' => 'datetime', 'bid_count' => 'integer', 'version' => 'integer']; }
    public function product(): BelongsTo { return $this->belongsTo(Product::class); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function winner(): BelongsTo { return $this->belongsTo(User::class, 'winner_id'); }
    public function bids(): HasMany { return $this->hasMany(Bid::class); }
    public function order(): HasOne { return $this->hasOne(Order::class); }
}
