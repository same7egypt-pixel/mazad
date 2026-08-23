<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\Order;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Collection;

class RevenueAndCommissionChart extends ChartWidget
{
    use ScopesMarketplaceData;

    protected ?string $heading = 'حجم المبيعات والعمولات';

    protected ?string $description = 'آخر 14 يوماً — تفصل العملات ولا تُجمع معاً.';

    protected ?string $pollingInterval = '30s';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('analytics.view') || $user?->can('payments.view') || $user?->hasRole('GLOBAL_SUPER_ADMIN');
    }

    protected function getData(): array
    {
        $days = collect(range(13, 0))->map(fn (int $offset): string => now()->subDays($offset)->toDateString());
        $rows = $this->scopeToAdminCountry(Order::query())
            ->whereNotNull('paid_at')
            ->where('paid_at', '>=', now()->subDays(13)->startOfDay())
            ->with('currency:id,code')
            ->get(['currency_id', 'amount', 'commission_amount', 'paid_at'])
            ->groupBy(fn (Order $order): string => ($order->currency?->code ?? 'غير محدد'));

        $datasets = $rows->flatMap(function (Collection $orders, string $currency) use ($days): array {
            $byDay = $orders->groupBy(fn (Order $order): string => $order->paid_at->toDateString());

            return [
                [
                    'label' => $currency.' حجم المبيعات',
                    'data' => $days->map(fn (string $day): float => (float) $byDay->get($day, collect())->sum('amount'))->all(),
                    'borderColor' => '#2dd4bf',
                    'backgroundColor' => 'rgba(45, 212, 191, 0.12)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
                [
                    'label' => $currency.' العمولات',
                    'data' => $days->map(fn (string $day): float => (float) $byDay->get($day, collect())->sum('commission_amount'))->all(),
                    'borderColor' => '#fb923c',
                    'backgroundColor' => 'rgba(251, 146, 60, 0.08)',
                    'tension' => 0.35,
                    'fill' => true,
                ],
            ];
        })->values()->all();

        return [
            'datasets' => $datasets,
            'labels' => $days->map(fn (string $day): string => now()->parse($day)->format('d/m'))->all(),
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}
