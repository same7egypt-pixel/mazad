<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\Auction;
use Filament\Widgets\ChartWidget;

class AuctionStatusChart extends ChartWidget
{
    use ScopesMarketplaceData;

    protected ?string $heading = 'حالة المزادات';

    protected ?string $description = 'توزيع المزادات الفعلي في نطاق الإدارة الحالي.';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('analytics.view') || $user?->can('auctions.manage') || $user?->hasRole('GLOBAL_SUPER_ADMIN');
    }

    protected function getData(): array
    {
        $labels = [
            'live' => 'حية',
            'scheduled' => 'مجدولة',
            'completed' => 'مكتملة',
            'cancelled' => 'ملغاة',
            'paused' => 'موقوفة',
        ];
        $counts = $this->scopeToAdminCountry(Auction::query())
            ->selectRaw('status, count(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return [
            'datasets' => [[
                'data' => collect($labels)->map(fn (string $label, string $status): int => (int) ($counts[$status] ?? 0))->values()->all(),
                'backgroundColor' => ['#2dd4bf', '#60a5fa', '#a78bfa', '#fb7185', '#fbbf24'],
                'borderWidth' => 0,
            ]],
            'labels' => array_values($labels),
        ];
    }

    protected function getType(): string
    {
        return 'doughnut';
    }
}
