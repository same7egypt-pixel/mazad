<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\AuditLog;
use Filament\Widgets\Widget;

class AuditActivityFeed extends Widget
{
    use ScopesMarketplaceData;

    protected string $view = 'filament.widgets.audit-activity-feed';

    protected ?string $pollingInterval = '30s';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('audit.view') || $user?->can('fraud.review') || $user?->hasRole('GLOBAL_SUPER_ADMIN');
    }

    protected function getViewData(): array
    {
        $activities = $this->scopeToAdminCountry(AuditLog::query())
            ->with('actor')
            ->latest()
            ->limit(8)
            ->get()
            ->map(fn (AuditLog $log): array => [
                'event' => match ($log->event) {
                    'auction.paused' => 'تم إيقاف مزاد مؤقتاً',
                    'auction.cancelled' => 'تم إلغاء مزاد',
                    'auction.extended' => 'تم تمديد مزاد',
                    'auction.bid_placed' => 'تم تسجيل مزايدة',
                    default => 'حدث تشغيلي موثق',
                },
                'actor' => $log->actor?->name ?? 'النظام',
                'subject' => $log->auditable_id ? '#'.$log->auditable_id : '—',
                'occurred_at' => $log->created_at,
            ]);

        return ['activities' => $activities];
    }
}
