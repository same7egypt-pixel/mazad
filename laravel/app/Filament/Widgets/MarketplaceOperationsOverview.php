<?php

namespace App\Filament\Widgets;

use App\Models\Auction;
use App\Models\Order;
use App\Models\Product;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceOperationsOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $stats = [
            Stat::make('منتجات بانتظار المراجعة', $this->scopeCountry(Product::query())->where('status', 'pending_review')->count())
                ->description('كتالوج يحتاج قرار مشرف')
                ->color('warning'),
            Stat::make('مزادات حية', $this->scopeCountry(Auction::query())->where('status', 'live')->count())
                ->description('مزادات مفتوحة للمزايدة الآن')
                ->color('success'),
            Stat::make('طلبات مدفوعة بانتظار التشغيل', $this->scopeCountry(Order::query())->where('status', 'paid')->count())
                ->description('تحتاج بدء مسار تنفيذ أو شحن')
                ->color('info'),
        ];

        $user = auth()->user();

        if ($user?->can('payments.manage') || $user?->hasRole('GLOBAL_SUPER_ADMIN')) {
            $withdrawals = Withdrawal::query()
                ->where('status', 'requested')
                ->whereHas('wallet.user', function (Builder $query) use ($user): void {
                    if (!$user?->hasRole('GLOBAL_SUPER_ADMIN') && $user?->country_id) {
                        $query->where('country_id', $user->country_id);
                    }
                })
                ->count();

            $stats[] = Stat::make('طلبات سحب للمراجعة', $withdrawals)
                ->description('متاح لمسؤولي المالية فقط')
                ->color('danger');
        }

        return $stats;
    }

    private function scopeCountry(Builder $query): Builder
    {
        $user = auth()->user();

        if ($user?->hasRole('GLOBAL_SUPER_ADMIN') || !$user?->country_id) {
            return $query;
        }

        return $query->where('country_id', $user->country_id);
    }
}
