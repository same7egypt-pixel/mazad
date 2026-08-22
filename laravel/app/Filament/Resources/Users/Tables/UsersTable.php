<?php

namespace App\Filament\Resources\Users\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class UsersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('country.name')
                    ->label('الدولة')
                    ->searchable(),
                TextColumn::make('city.name')
                    ->label('المدينة')
                    ->searchable(),
                TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable(),
                TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable(),
                TextColumn::make('phone')
                    ->label('الهاتف')
                    ->searchable(),
                TextColumn::make('email_verified_at')
                    ->label('توثيق البريد')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('verification_status')
                    ->label('حالة التحقق')
                    ->badge()
                    ->searchable(),
                TextColumn::make('status')
                    ->label('حالة الحساب')
                    ->badge()
                    ->searchable(),
                TextColumn::make('last_login_at')
                    ->label('آخر دخول')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->recordActions([])
            ->toolbarActions([]);
    }
}
