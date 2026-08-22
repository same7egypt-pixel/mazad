<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingProvider extends Model
{
    protected $fillable = ['country_id', 'name', 'code', 'provider_type', 'configuration', 'is_active'];
    protected $hidden = ['configuration'];
    protected function casts(): array { return ['configuration' => 'encrypted:array', 'is_active' => 'boolean']; }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function shipments(): HasMany { return $this->hasMany(Shipment::class, 'provider_id'); }
}
