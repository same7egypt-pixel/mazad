<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Order extends Model
{
    use LogsActivity;

    protected $fillable = ['auction_id', 'seller_id', 'buyer_id', 'country_id', 'currency_id', 'amount', 'commission_rate', 'commission_amount', 'seller_amount', 'status', 'payment_method', 'winner_confirmed_at', 'winner_confirmation_expires_at', 'fulfilment_preference', 'shipping_fee', 'collection_status', 'settlement_status', 'receipt_confirmed_at', 'settled_at', 'collection_failure_reason', 'paid_at', 'completed_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'commission_rate' => 'decimal:2', 'commission_amount' => 'decimal:2', 'seller_amount' => 'decimal:2', 'shipping_fee' => 'decimal:2', 'winner_confirmed_at' => 'datetime', 'winner_confirmation_expires_at' => 'datetime', 'receipt_confirmed_at' => 'datetime', 'settled_at' => 'datetime', 'paid_at' => 'datetime', 'completed_at' => 'datetime'];
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()->logOnly(['status', 'payment_method', 'winner_confirmed_at', 'fulfilment_preference', 'collection_status', 'settlement_status', 'receipt_confirmed_at', 'settled_at', 'collection_failure_reason'])->logOnlyDirty();
    }

    public function auction(): BelongsTo
    {
        return $this->belongsTo(Auction::class);
    }

    public function seller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function currency(): BelongsTo
    {
        return $this->belongsTo(Currency::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function shipment(): HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }
}
