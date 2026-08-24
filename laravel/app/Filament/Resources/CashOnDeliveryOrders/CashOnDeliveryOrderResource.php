<?php

namespace App\Filament\Resources\CashOnDeliveryOrders;

use App\Filament\Resources\CashOnDeliveryOrders\Pages\ListCashOnDeliveryOrders;
use App\Filament\Resources\CashOnDeliveryOrders\Tables\CashOnDeliveryOrdersTable;
use App\Models\Order;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class CashOnDeliveryOrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|\UnitEnum|null $navigationGroup = 'الطلبات';

    protected static ?string $navigationLabel = 'تحصيل عند الاستلام';

    protected static ?string $modelLabel = 'طلب دفع عند الاستلام';

    protected static ?string $pluralModelLabel = 'طلبات الدفع عند الاستلام';

    protected static ?int $navigationSort = 11;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['auction.product', 'seller', 'buyer', 'country', 'currency', 'shipment'])
            ->where('payment_method', 'cash_on_delivery');
        $user = auth()->user();

        if ($user?->hasRole('GLOBAL_SUPER_ADMIN') || ! $user?->country_id) {
            return $query;
        }

        return $query->where('country_id', $user->country_id);
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getEloquentQuery()
            ->whereIn('collection_status', ['awaiting_confirmation', 'awaiting_collection'])
            ->count();

        return $pending ? (string) $pending : null;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('orders.view') ?? false;
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([]);
    }

    public static function table(Table $table): Table
    {
        return CashOnDeliveryOrdersTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCashOnDeliveryOrders::route('/'),
        ];
    }
}
