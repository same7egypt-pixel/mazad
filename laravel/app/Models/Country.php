<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Country extends Model
{
    use LogsActivity;

    protected $fillable = ['name', 'code', 'timezone', 'currency_id', 'platform_commission_rate', 'is_active'];
    protected function casts(): array { return ['platform_commission_rate' => 'decimal:2', 'is_active' => 'boolean']; }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'code', 'platform_commission_rate', 'is_active'])
            ->logOnlyDirty();
    }

    public function currency(): BelongsTo { return $this->belongsTo(Currency::class); }
    public function cities(): HasMany { return $this->hasMany(City::class); }
    public function users(): HasMany { return $this->hasMany(User::class); }
    public function products(): HasMany { return $this->hasMany(Product::class); }
    public function auctions(): HasMany { return $this->hasMany(Auction::class); }
}
