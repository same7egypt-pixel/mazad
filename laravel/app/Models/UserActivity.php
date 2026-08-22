<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserActivity extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['user_id', 'country_id', 'event', 'properties', 'ip_address', 'user_agent'];
    protected function casts(): array { return ['properties' => 'array', 'created_at' => 'datetime']; }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
}
