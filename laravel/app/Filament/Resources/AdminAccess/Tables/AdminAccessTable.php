<?php

namespace App\Filament\Resources\AdminAccess\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AdminAccessTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('الاسم')->searchable(),
                TextColumn::make('email')->label('البريد الإلكتروني')->searchable(),
                TextColumn::make('country.name')->label('الدولة')->searchable(),
                TextColumn::make('roles.name')->label('الأدوار')->badge(),
                TextColumn::make('status')->label('حالة الحساب')->badge(),
                TextColumn::make('email_verified_at')->label('توثيق البريد')->dateTime()->sortable(),
            ])
            ->filters([])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([]);
    }
}
