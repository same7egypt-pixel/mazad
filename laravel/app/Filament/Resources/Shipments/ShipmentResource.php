<?php

namespace App\Filament\Resources\Shipments;

use App\Filament\Resources\Shipments\Pages\ListShipments;
use App\Filament\Resources\Shipments\Schemas\ShipmentForm;
use App\Filament\Resources\Shipments\Tables\ShipmentsTable;
use App\Models\Shipment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Table;

class ShipmentResource extends Resource
{
    protected static ?string $model = Shipment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'tracking_number';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['order.country', 'provider']);
        $user = auth()->user();

        if ($user?->hasRole('GLOBAL_SUPER_ADMIN') || !$user?->country_id) {
            return $query;
        }

        return $query->whereHas('order', fn (Builder $order): Builder => $order->where('country_id', $user->country_id));
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('shipping.manage') ?? false;
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return false;
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return ShipmentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ShipmentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListShipments::route('/'),
        ];
    }
}
