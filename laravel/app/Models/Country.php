<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Country extends Model
{
    protected $fillable = ['name', 'code', 'timezone', 'currency_id', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean']; }
    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function cities(): HasMany { return $this->hasMany(City::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function auctions(): HasMany { return $this->hasMany(Auction::class); }
}
