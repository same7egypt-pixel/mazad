<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model
{
    protected $fillable = ['wallet_id', 'type', 'amount', 'reference_type', 'reference_id', 'status', 'metadata'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'metadata' => 'array']; }
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
}

