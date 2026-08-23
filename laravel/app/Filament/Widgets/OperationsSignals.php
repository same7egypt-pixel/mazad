<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Withdrawal;
use Filament\Widgets\Widget;
use Illuminate\Database\Eloquent\Builder;

class OperationsSignals extends Widget
{
    use ScopesMarketplaceData;

    protected string $view = 'filament.widgets.operations-signals';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('products.approve') || $user?->can('payments.view') || $user?->can('payments.manage') || $user?->hasRole('GLOBAL_SUPER_ADMIN');
    }

    protected function getViewData(): array
    {
        $countryId = $this->scopedCountryId();
        $withdrawals = Withdrawal::query()
            ->where('status', 'requested')
            ->whereHas('wallet.user', function (Builder $query) use ($countryId): void {
                if ($countryId) {
                    $query->where('country_id', $countryId);
                }
            })
            ->count();

        $user = $this->actingAdmin();
        $signals = [];

        if ($user?->can('products.approve') || $this->isGlobalAdmin()) {
            $signals[] = ['label' => 'منتجات تحتاج مراجعة', 'value' => $this->scopeToAdminCountry(Product::query())->where('status', 'pending_review')->count(), 'href' => '/admin/products?tableFilters[status][value]=pending_review', 'tone' => 'warning'];
        }

        if ($user?->can('payments.view') || $this->isGlobalAdmin()) {
            $signals[] = ['label' => 'مدفوعات لم تكتمل', 'value' => $this->scopeToAdminCountry(Payment::query())->whereIn('status', ['failed', 'pending'])->count(), 'href' => '/admin/orders', 'tone' => 'danger'];
        }

        if ($user?->can('payments.manage') || $this->isGlobalAdmin()) {
            $signals[] = ['label' => 'سحوبات بانتظار المراجعة', 'value' => $withdrawals, 'href' => '/admin/withdrawals', 'tone' => 'info'];
        }

        return ['signals' => $signals];
    }
}
