<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\Auction;
use App\Models\Country;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class MarketplaceOperationsOverview extends StatsOverviewWidget
{
    use ScopesMarketplaceData;

    protected ?string $pollingInterval = '30s';

    protected function getStats(): array
    {
        $countryId = $this->scopedCountryId();
        $today = now()->startOfDay();
        $users = $this->scopeToAdminCountry(User::query());
        $auctions = $this->scopeToAdminCountry(Auction::query());
        $orders = $this->scopeToAdminCountry(Order::query());
        $products = $this->scopeToAdminCountry(Product::query());

        $user = $this->actingAdmin();
        $stats = [
            Stat::make('الأسواق النشطة', $countryId ? 1 : Country::query()->where('is_active', true)->count())
                ->description($countryId ? 'السوق المصرح لك بإدارته' : 'الأسواق المفعلة في المنصة')
                ->icon('heroicon-o-globe-alt')
                ->color('primary'),
        ];

        if ($user?->can('users.manage') || $this->isGlobalAdmin()) {
            $stats[] = Stat::make('المستخدمون', $users->count())
                ->description($users->clone()->where('created_at', '>=', $today)->count().' مستخدم جديد اليوم')
                ->icon('heroicon-o-users')
                ->color('info');
        }

        if ($user?->can('auctions.manage') || $this->isGlobalAdmin()) {
            $stats[] = Stat::make('المزادات النشطة', $auctions->clone()->where('status', 'live')->count())
                ->description($auctions->clone()->where('status', 'live')->where('end_time', '<=', now()->addHour())->count().' تنتهي خلال ساعة')
                ->icon('heroicon-o-gavel')
                ->color('success');
        }

        if ($user?->can('products.approve') || $this->isGlobalAdmin()) {
            $stats[] = Stat::make('منتجات بانتظار المراجعة', $products->where('status', 'pending_review')->count())
                ->description('تحتاج قرار مشرف قبل النشر')
                ->icon('heroicon-o-clipboard-document-check')
                ->color('warning');
        }

        if ($user?->can('orders.view') || $this->isGlobalAdmin()) {
            $stats[] = Stat::make('طلبات مكتملة', $orders->clone()->where('status', 'completed')->count())
                ->description($orders->clone()->whereNotNull('paid_at')->count().' طلباً مدفوعاً في النطاق')
                ->icon('heroicon-o-check-circle')
                ->color('success');
        }

        if ($user?->can('payments.manage') || $this->isGlobalAdmin()) {
            $withdrawals = Withdrawal::query()
                ->where('status', 'requested')
                ->whereHas('wallet.user', function (Builder $query) use ($countryId): void {
                    if ($countryId) {
                        $query->where('country_id', $countryId);
                    }
                })
                ->count();

            $stats[] = Stat::make('سحوبات بانتظار المراجعة', $withdrawals)
                ->description('متاحة لمسؤولي المالية فقط')
                ->icon('heroicon-o-banknotes')
                ->color($withdrawals ? 'danger' : 'gray');
        }

        return $stats;
    }
}
