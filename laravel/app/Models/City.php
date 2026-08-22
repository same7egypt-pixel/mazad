<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class City extends Model
{
    protected $fillable = ['country_id', 'name', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function country(): BelongsTo { return $this->belongsTo(Country::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
}
