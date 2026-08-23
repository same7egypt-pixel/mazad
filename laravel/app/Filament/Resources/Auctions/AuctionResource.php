<?php

namespace App\Filament\Resources\Auctions;

use App\Filament\Resources\Auctions\Pages\ListAuctions;
use App\Filament\Resources\Auctions\Schemas\AuctionForm;
use App\Filament\Resources\Auctions\Tables\AuctionsTable;
use App\Models\Auction;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AuctionResource extends Resource
{
    protected static ?string $model = Auction::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static string|\UnitEnum|null $navigationGroup = 'السوق';

    protected static ?int $navigationSort = 20;

    protected static ?string $recordTitleAttribute = 'id';

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with(['product', 'country', 'currency', 'winner']);
        $user = auth()->user();

        if ($user?->hasRole('GLOBAL_SUPER_ADMIN') || ! $user?->country_id) {
            return $query;
        }

        return $query->where('country_id', $user->country_id);
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('auctions.manage') ?? false;
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
        return AuctionForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AuctionsTable::configure($table);
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
            'index' => ListAuctions::route('/'),
        ];
    }
}
