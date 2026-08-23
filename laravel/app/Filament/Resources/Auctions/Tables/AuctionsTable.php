<?php

namespace App\Filament\Resources\Auctions\Tables;

use App\Domain\Auctions\Services\ControlAuction;
use App\Models\Auction;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Textarea;
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
            ])
            ->recordActions([
                Action::make('pause')
                    ->label('إيقاف مؤقت')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('سبب الإيقاف')->required()->maxLength(500)])
                    ->visible(fn (Auction $record): bool => self::canControl($record) && in_array($record->status, ['upcoming', 'live'], true))
                    ->action(function (Auction $record, array $data): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(ControlAuction::class)->pause($record->id, $actor, $data['reason']);
                    }),
                Action::make('extend')
                    ->label('تمديد')
                    ->color('info')
                    ->requiresConfirmation()
                    ->schema([
                        DateTimePicker::make('end_time')->label('وقت النهاية الجديد')->seconds(false)->required(),
                        Textarea::make('reason')->label('سبب التمديد')->required()->maxLength(500),
                    ])
                    ->visible(fn (Auction $record): bool => self::canControl($record) && $record->status === 'live')
                    ->action(function (Auction $record, array $data): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(ControlAuction::class)->extend($record->id, $actor, $data['end_time'], $data['reason']);
                    }),
                Action::make('cancel')
                    ->label('إلغاء')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->schema([Textarea::make('reason')->label('سبب الإلغاء')->required()->maxLength(500)])
                    ->visible(fn (Auction $record): bool => self::canControl($record) && in_array($record->status, ['upcoming', 'live', 'paused'], true))
                    ->action(function (Auction $record, array $data): void {
                        /** @var User $actor */
                        $actor = auth()->user();
                        app(ControlAuction::class)->cancel($record->id, $actor, $data['reason']);
                    }),
            ]);
    }

    private static function canControl(Auction $auction): bool
    {
        $actor = auth()->user();

        return $actor instanceof User
            && $actor->can('auctions.manage')
            && ($actor->hasRole('GLOBAL_SUPER_ADMIN') || $actor->country_id === $auction->country_id);
    }
}
