<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    protected $fillable = ['auction_id', 'seller_id', 'buyer_id', 'country_id', 'currency_id', 'amount', 'commission_amount', 'seller_amount', 'status', 'paid_at', 'completed_at'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'commission_amount' => 'decimal:2', 'seller_amount' => 'decimal:2', 'paid_at' => 'datetime', 'completed_at' => 'datetime']; }
    public function auction(): BelongsTo { return $this->belongsTo(Auction::class); }
    public function seller(): BelongsTo { return $this->belongsTo(User::class, 'seller_id'); }
    public function buyer(): BelongsTo { return $this->belongsTo(User::class, 'buyer_id'); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function payments(): HasMany { return $this->hasMany(Payment::class); }
    public function shipment(): HasOne { return $this->hasOne(Shipment::class); }
    public function reviews(): HasMany { return $this->hasMany(Review::class); }
}
