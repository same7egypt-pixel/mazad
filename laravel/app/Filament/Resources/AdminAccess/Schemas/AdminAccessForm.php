<?php

namespace App\Filament\Resources\AdminAccess\Schemas;

use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Placeholder;
use Filament\Schemas\Schema;

class AdminAccessForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Placeholder::make('account')
                    ->label('الحساب')
                    ->content(fn ($record): string => "{$record->name} — {$record->email}"),
                Placeholder::make('marketplace')
                    ->label('سياق الدولة')
                    ->content(fn ($record): string => $record->country?->name ?? 'بدون دولة محددة'),
                CheckboxList::make('roles')
                    ->label('الأدوار الإدارية')
                    ->relationship('roles', 'name')
                    ->columns(2)
                    ->required(),
            ]);
    }
}
