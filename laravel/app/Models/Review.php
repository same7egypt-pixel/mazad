<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = ['reviewer_id', 'reviewed_user_id', 'order_id', 'rating', 'comment'];
    protected function casts(): array { return ['rating' => 'integer']; }
    public function reviewer(): BelongsTo { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function reviewedUser(): BelongsTo { return $this->belongsTo(User::class, 'reviewed_user_id'); }
    public function order(): BelongsTo { return $this->belongsTo(Order::class); }
}
