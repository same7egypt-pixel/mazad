<?php

namespace App\Filament\Resources\CashOnDeliveryOrders\Pages;

use App\Filament\Resources\CashOnDeliveryOrders\CashOnDeliveryOrderResource;
use App\Filament\Resources\CashOnDeliveryOrders\Widgets\CashOnDeliveryOverview;
use Filament\Resources\Pages\ListRecords;

class ListCashOnDeliveryOrders extends ListRecords
{
    protected static string $resource = CashOnDeliveryOrderResource::class;

    protected function getHeaderWidgets(): array
    {
        return [
            CashOnDeliveryOverview::class,
        ];
    }
}
