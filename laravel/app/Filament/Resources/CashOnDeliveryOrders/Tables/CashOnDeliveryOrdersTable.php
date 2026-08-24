<?php

namespace App\Filament\Resources\CashOnDeliveryOrders\Tables;

use App\Domain\Payments\Services\MarkCashOnDeliveryCollectionFailed;
use App\Domain\Payments\Services\RecordCashOnDeliveryCollection;
use App\Models\Order;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class CashOnDeliveryOrdersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('رقم الطلب')
                    ->sortable(),
                TextColumn::make('auction.product.title')
                    ->label('القطعة')
                    ->limit(36)
                    ->tooltip(fn (Order $record): string => $record->auction?->product?->title ?? '—'),
                TextColumn::make('buyer.name')
                    ->label('الفائز')
                    ->searchable(),
                TextColumn::make('seller.name')
                    ->label('البائع')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('fulfilment_preference')
                    ->label('طريقة الاستلام')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'external' => 'توصيل',
                        'self_pickup' => 'استلام ذاتي',
                        default => 'لم تؤكد بعد',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'external' => 'info',
                        'self_pickup' => 'primary',
                        default => 'gray',
                    }),
                TextColumn::make('collection_status')
                    ->label('حالة التحصيل')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'awaiting_confirmation' => 'بانتظار تأكيد الفائز',
                        'awaiting_collection' => 'بانتظار التحصيل',
                        'collected' => 'تم التحصيل',
                        'collection_failed' => 'تعذر التحصيل',
                        default => $state ?: '—',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'collected' => 'success',
                        'collection_failed' => 'danger',
                        'awaiting_confirmation', 'awaiting_collection' => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('shipment.status')
                    ->label('التسليم')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'delivered' => 'تم التسليم',
                        'ready_for_pickup' => 'جاهز للاستلام',
                        'shipped' => 'تم الشحن',
                        'preparing' => 'قيد التجهيز',
                        default => $state ?: 'لم تُنشأ شحنة',
                    }),
                TextColumn::make('settlement_status')
                    ->label('تسوية البائع')
                    ->badge()
                    ->formatStateUsing(fn (?string $state): string => match ($state) {
                        'released' => 'متاح للبائع',
                        'pending_release' => 'بانتظار التحرير',
                        'held' => 'محجوزة',
                        default => $state ?: 'معلّقة',
                    })
                    ->color(fn (?string $state): string => match ($state) {
                        'released' => 'success',
                        'held' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('amount')
                    ->label('إجمالي الطلب')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('winner_confirmation_expires_at')
                    ->label('مهلة التأكيد')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('collection_failure_reason')
                    ->label('سبب تعذر التحصيل')
                    ->limit(32)
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('collection_status')
                    ->label('حالة التحصيل')
                    ->options([
                        'awaiting_confirmation' => 'بانتظار تأكيد الفائز',
                        'awaiting_collection' => 'بانتظار التحصيل',
                        'collected' => 'تم التحصيل',
                        'collection_failed' => 'تعذر التحصيل',
                    ]),
                SelectFilter::make('fulfilment_preference')
                    ->label('طريقة الاستلام')
                    ->options(['external' => 'توصيل', 'self_pickup' => 'استلام ذاتي']),
                SelectFilter::make('settlement_status')
                    ->label('تسوية البائع')
                    ->options(['pending_release' => 'بانتظار التحرير', 'released' => 'متاح للبائع', 'held' => 'محجوزة']),
            ])
            ->defaultSort('created_at', 'desc')
            ->emptyStateHeading('لا توجد طلبات دفع عند الاستلام في نطاقك حالياً')
            ->emptyStateDescription('ستظهر هنا الطلبات الفائزة فور تفعيل الدفع عند الاستلام وتأكيد العميل.')
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
                    ->visible(fn (Order $record): bool => auth()->user()?->can('orders.fulfill') && $record->collection_status === 'awaiting_collection')
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
                    ->visible(fn (Order $record): bool => auth()->user()?->can('orders.fulfill') && $record->collection_status === 'awaiting_collection')
                    ->action(function (Order $record, array $data): void {
                        app(MarkCashOnDeliveryCollectionFailed::class)->handle($record->id, auth()->user(), $data['reason']);
                        Notification::make()->title('سُجل تعذر التحصيل وحُجزت التسوية.')->danger()->send();
                    }),
            ]);
    }
}
