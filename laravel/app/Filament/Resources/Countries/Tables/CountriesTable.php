<?php

namespace App\Filament\Resources\Countries\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CountriesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('code')
                    ->searchable(),
                TextColumn::make('timezone')
                    ->searchable(),
                TextColumn::make('currency.name')
                    ->searchable(),
                IconColumn::make('is_active')
                    ->boolean(),
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
                TernaryFilter::make('is_active')
                    ->label('حالة السوق'),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                // حذف الدولة معطل لحماية السجلات المرجعية؛ أوقفها بدلاً من ذلك.
            ]);
    }
}
