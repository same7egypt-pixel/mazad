<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\Order;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class RecentOrdersTable extends TableWidget
{
    use ScopesMarketplaceData;

    protected static ?string $heading = 'أحدث المعاملات';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('orders.view') || $user?->hasRole('GLOBAL_SUPER_ADMIN');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->scopeToAdminCountry(Order::query())
                    ->with(['auction', 'buyer', 'seller', 'currency'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('auction_id')->label('المزاد')->formatStateUsing(fn (Order $record): string => '#'.$record->auction_id)->sortable(),
                TextColumn::make('buyer.name')->label('المشتري')->placeholder('—'),
                TextColumn::make('seller.name')->label('البائع')->placeholder('—'),
                TextColumn::make('amount')->label('المبلغ')->money(fn (Order $record): ?string => $record->currency?->code)->sortable(),
                TextColumn::make('status')->label('الحالة')->badge(),
                TextColumn::make('created_at')->label('الوقت')->since()->sortable(),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد معاملات في هذا النطاق بعد')
            ->emptyStateDescription('ستظهر الطلبات المنشأة من المزادات الحقيقية هنا.');
    }
}
