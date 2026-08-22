<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Currency extends Model
{
    protected $fillable = ['name', 'code', 'symbol', 'decimal_places', 'is_active'];
    protected function casts(): array { return ['is_active' => 'boolean', 'decimal_places' => 'integer']; }
    public function countries(): HasMany { return $this->hasMany(Country::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function auctions(): HasMany { return $this->hasMany(Auction::class); }
}
