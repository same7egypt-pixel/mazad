<?php

namespace App\Filament\Resources\Orders\Tables;

use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class OrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('auction.id')
                    ->searchable(),
                TextColumn::make('seller.name')
                    ->searchable(),
                TextColumn::make('buyer.name')
                    ->searchable(),
                TextColumn::make('country.name')
                    ->searchable(),
                TextColumn::make('currency.name')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('commission_rate')
                    ->label('نسبة العمولة')
                    ->suffix('%')
                    ->numeric(decimalPlaces: 2)
                    ->sortable(),
                TextColumn::make('commission_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('seller_amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'completed' => 'success',
                        'waiting_payment', 'fulfillment_pending' => 'warning',
                        'shipped', 'ready_for_pickup' => 'info',
                        'cancelled', 'failed' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('completed_at')
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
                        'waiting_payment' => 'بانتظار الدفع',
                        'paid' => 'مدفوع',
                        'fulfillment_pending' => 'بانتظار التنفيذ',
                        'shipped' => 'تم الشحن',
                        'ready_for_pickup' => 'جاهز للاستلام',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغى',
                    ]),
            ]);
    }
}
