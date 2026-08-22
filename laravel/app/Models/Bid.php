<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bid extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['auction_id', 'user_id', 'amount'];
    protected function casts(): array { return ['amount' => 'decimal:2', 'created_at' => 'datetime']; }
    public function auction(): BelongsTo { return $this->belongsTo(Auction::class); }
    public function bidder(): BelongsTo { return $this->belongsTo(User::class, 'user_id'); }
}
