<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Shipment extends Model
{
    protected $fillable = ['order_id', 'provider_id', 'fulfilment_type', 'tracking_number', 'status', 'shipping_address', 'shipped_at', 'delivered_at'];
    protected function casts(): array { return ['shipping_address' => 'encrypted:array', 'shipped_at' => 'datetime', 'delivered_at' => 'datetime']; }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
    public function provider(): BelongsTo { return $this->belongsTo(ShippingProvider::class, 'provider_id'); }
}
