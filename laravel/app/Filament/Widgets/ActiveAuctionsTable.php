<?php

namespace App\Filament\Widgets;

use App\Filament\Concerns\ScopesMarketplaceData;
use App\Models\Auction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;

class ActiveAuctionsTable extends TableWidget
{
    use ScopesMarketplaceData;

    protected static ?string $heading = 'المزادات النشطة';

    protected int|string|array $columnSpan = 'full';

    public static function canView(): bool
    {
        $user = auth()->user();

        return $user?->can('auctions.manage') || $user?->hasRole('GLOBAL_SUPER_ADMIN');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                $this->scopeToAdminCountry(Auction::query())
                    ->where('status', 'live')
                    ->with(['product', 'currency'])
                    ->orderBy('end_time')
                    ->limit(10)
            )
            ->columns([
                TextColumn::make('product.title')->label('القطعة')->searchable()->placeholder('—'),
                TextColumn::make('current_price')->label('المزايدة الحالية')->money(fn (Auction $record): ?string => $record->currency?->code)->sortable(),
                TextColumn::make('bid_count')->label('عدد المزايدين')->numeric()->sortable(),
                TextColumn::make('end_time')->label('الوقت المتبقي')->since()->sortable(),
                TextColumn::make('status')->label('الحالة')->badge()->color('success'),
            ])
            ->paginated(false)
            ->emptyStateHeading('لا توجد مزادات نشطة في هذا النطاق')
            ->emptyStateDescription('ستظهر المزادات المفتوحة للمزايدة هنا فور تفعيلها.');
    }
}
