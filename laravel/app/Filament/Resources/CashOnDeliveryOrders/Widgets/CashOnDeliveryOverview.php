<?php

namespace App\Filament\Resources\CashOnDeliveryOrders\Widgets;

use App\Filament\Resources\CashOnDeliveryOrders\CashOnDeliveryOrderResource;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CashOnDeliveryOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $orders = CashOnDeliveryOrderResource::getEloquentQuery();
        $awaitingConfirmation = $orders->clone()->where('collection_status', 'awaiting_confirmation')->count();
        $awaitingCollection = $orders->clone()->where('collection_status', 'awaiting_collection')->count();
        $failed = $orders->clone()->where('collection_status', 'collection_failed')->count();
        $released = $orders->clone()->where('settlement_status', 'released')->count();

        return [
            Stat::make('تأكيدات الفائزين', $awaitingConfirmation)
                ->description('طلبات تنتظر قرار العميل')
                ->icon('heroicon-o-clock')
                ->color($awaitingConfirmation ? 'warning' : 'gray'),
            Stat::make('بانتظار التحصيل', $awaitingCollection)
                ->description('طلبات مؤكدة تحتاج تسجيل التحصيل')
                ->icon('heroicon-o-banknotes')
                ->color($awaitingCollection ? 'warning' : 'gray'),
            Stat::make('تعذر تحصيلها', $failed)
                ->description('تبقى التسوية محجوزة للمراجعة')
                ->icon('heroicon-o-exclamation-triangle')
                ->color($failed ? 'danger' : 'gray'),
            Stat::make('مستحقات محررة', $released)
                ->description('طلب مكتمل بعد تأكيد الاستلام')
                ->icon('heroicon-o-check-circle')
                ->color($released ? 'success' : 'gray'),
        ];
    }
}
