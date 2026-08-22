<?php

namespace App\Filament\Resources\Shipments\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ShipmentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('order.id')
                    ->searchable(),
                TextColumn::make('provider.name')
                    ->searchable(),
                TextColumn::make('fulfilment_type')
                    ->searchable(),
                TextColumn::make('tracking_number')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'delivered' => 'success',
                        'shipped', 'ready_for_pickup' => 'info',
                        'pending', 'prepared' => 'warning',
                        'cancelled', 'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('shipped_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('delivered_at')
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
                SelectFilter::make('status')
                    ->options([
                        'pending' => 'قيد التجهيز',
                        'prepared' => 'مجهز',
                        'shipped' => 'تم الشحن',
                        'ready_for_pickup' => 'جاهز للاستلام',
                        'delivered' => 'تم التسليم',
                    ]),
            ]);
    }
}
