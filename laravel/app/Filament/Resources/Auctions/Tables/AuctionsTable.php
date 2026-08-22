<?php

namespace App\Filament\Resources\Auctions\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AuctionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.title')
                    ->searchable(),
                TextColumn::make('country.name')
                    ->searchable(),
                TextColumn::make('currency.name')
                    ->searchable(),
                TextColumn::make('starting_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('current_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('reserve_price')
                    ->money()
                    ->sortable(),
                TextColumn::make('minimum_increment')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('start_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('end_time')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'live' => 'success',
                        'upcoming' => 'warning',
                        'cancelled', 'ended_without_sale' => 'danger',
                        'ended', 'sold' => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('winner.name')
                    ->searchable(),
                TextColumn::make('bid_count')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('version')
                    ->numeric()
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
                SelectFilter::make('status')
                    ->options([
                        'upcoming' => 'قادم',
                        'live' => 'حي',
                        'sold' => 'تم البيع',
                        'ended' => 'منتهٍ',
                        'ended_without_sale' => 'انتهى بلا بيع',
                        'cancelled' => 'ملغى',
                    ]),
            ]);
    }
}
