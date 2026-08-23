<?php

namespace App\Filament\Resources\Orders\Tables;

use App\Domain\Payments\Services\MarkCashOnDeliveryCollectionFailed;
use App\Domain\Payments\Services\RecordCashOnDeliveryCollection;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
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
                TextColumn::make('payment_method')
                    ->label('طريقة الدفع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $state === 'cash_on_delivery' ? 'عند الاستلام' : 'إلكتروني'),
                TextColumn::make('collection_status')
                    ->label('التحصيل')
                    ->badge()
                    ->toggleable(),
                TextColumn::make('settlement_status')
                    ->label('تسوية البائع')
                    ->badge()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid', 'completed' => 'success',
                        'waiting_payment', 'awaiting_cod_confirmation', 'cod_confirmed', 'fulfillment_pending', 'awaiting_collection', 'awaiting_receipt_confirmation' => 'warning',
                        'shipped', 'ready_for_pickup' => 'info',
                        'cancelled', 'failed', 'collection_failed' => 'danger',
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
                        'awaiting_cod_confirmation' => 'بانتظار تأكيد الفائز',
                        'cod_confirmed' => 'تم تأكيد COD',
                        'paid' => 'مدفوع',
                        'fulfillment_pending' => 'بانتظار التنفيذ',
                        'shipped' => 'تم الشحن',
                        'ready_for_pickup' => 'جاهز للاستلام',
                        'awaiting_collection' => 'بانتظار التحصيل',
                        'awaiting_receipt_confirmation' => 'بانتظار تأكيد الاستلام',
                        'collection_failed' => 'تعذر التحصيل',
                        'completed' => 'مكتمل',
                        'cancelled' => 'ملغى',
                    ]),
            ])
            ->recordActions([
                Action::make('record_cash_collection')
                    ->label('تسجيل التحصيل')
                    ->color('success')
                    ->form([
                        TextInput::make('collection_reference')
                            ->label('مرجع التحصيل')
                            ->maxLength(191),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => auth()->user()?->can('orders.fulfill') && $record->payment_method === 'cash_on_delivery' && $record->collection_status === 'awaiting_collection')
                    ->action(function (Order $record, array $data): void {
                        app(RecordCashOnDeliveryCollection::class)->handle($record->id, auth()->user(), $data['collection_reference'] ?? null);
                        Notification::make()->title('تم تسجيل التحصيل ووضع مستحق البائع في الرصيد المعلّق.')->success()->send();
                    }),
                Action::make('mark_cash_collection_failed')
                    ->label('تعذر التحصيل')
                    ->color('danger')
                    ->form([
                        TextInput::make('reason')
                            ->label('سبب التعذر')
                            ->required()
                            ->maxLength(255),
                    ])
                    ->requiresConfirmation()
                    ->visible(fn (Order $record): bool => auth()->user()?->can('orders.fulfill') && $record->payment_method === 'cash_on_delivery' && $record->collection_status === 'awaiting_collection')
                    ->action(function (Order $record, array $data): void {
                        app(MarkCashOnDeliveryCollectionFailed::class)->handle($record->id, auth()->user(), $data['reason']);
                        Notification::make()->title('سُجل تعذر التحصيل وحُجزت التسوية.')->danger()->send();
                    }),
            ]);
    }
}
