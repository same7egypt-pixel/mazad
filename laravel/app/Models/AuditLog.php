<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    public const UPDATED_AT = null;
    protected $fillable = ['actor_id', 'country_id', 'event', 'before', 'after', 'ip_address', 'user_agent'];
    protected function casts(): array { return ['before' => 'array', 'after' => 'array', 'created_at' => 'datetime']; }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function auditable(): MorphTo { return $this->morphTo(); }
}
