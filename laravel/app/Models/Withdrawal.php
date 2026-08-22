<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Withdrawal extends Model
{
    protected $fillable = ['wallet_id', 'amount', 'status', 'destination_type', 'destination_details', 'reviewed_by', 'processed_at'];
    protected $hidden = ['destination_details'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'destination_details' => 'encrypted:array', 'processed_at' => 'datetime']; }
    public function wallet(): BelongsTo { return $this->belongsTo(Wallet::class); }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_by'); }
}
