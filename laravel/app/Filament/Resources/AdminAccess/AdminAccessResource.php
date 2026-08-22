<?php

namespace App\Filament\Resources\AdminAccess;

use App\Filament\Resources\AdminAccess\Pages\EditAdminAccess;
use App\Filament\Resources\AdminAccess\Pages\ListAdminAccess;
use App\Filament\Resources\AdminAccess\Schemas\AdminAccessForm;
use App\Filament\Resources\AdminAccess\Tables\AdminAccessTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class AdminAccessResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?string $navigationLabel = 'إدارة وصول المسؤولين';

    protected static ?string $recordTitleAttribute = 'name';

    public static function canViewAny(): bool
    {
        $user = auth()->user();

        return $user?->hasRole('GLOBAL_SUPER_ADMIN') && $user->can('roles.manage');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit(Model $record): bool
    {
        return static::canViewAny();
    }

    public static function canDelete(Model $record): bool
    {
        return false;
    }

    public static function form(Schema $schema): Schema
    {
        return AdminAccessForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AdminAccessTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAdminAccess::route('/'),
            'edit' => EditAdminAccess::route('/{record}/edit'),
        ];
    }
}
