<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    protected $fillable = ['order_id', 'country_id', 'gateway', 'transaction_id', 'amount', 'currency', 'status', 'collected_at', 'collection_reference', 'collection_failure_reason', 'payload'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'collected_at' => 'datetime', 'payload' => 'array'];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }
}
