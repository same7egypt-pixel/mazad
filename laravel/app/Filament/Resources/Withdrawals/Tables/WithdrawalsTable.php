<?php

namespace App\Filament\Resources\Withdrawals\Tables;

use App\Domain\Payments\Services\ReviewWithdrawal;
use App\Models\User;
use App\Models\Withdrawal;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class WithdrawalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('wallet.user.name')
                    ->label('البائع')
                    ->searchable(),
                TextColumn::make('amount')
                    ->numeric()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved' => 'success',
                        'requested' => 'warning',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('destination_type')
                    ->searchable(),
                TextColumn::make('reviewer.name')
                    ->label('راجعه')
                    ->placeholder('—'),
                TextColumn::make('processed_at')
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
                        'requested' => 'بانتظار المراجعة',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('اعتماد')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record): bool => self::canReview($record))
                    ->action(function (Withdrawal $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        app(ReviewWithdrawal::class)->approve($record->id, $reviewer);
                    }),
                Action::make('reject')
                    ->label('رفض')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record): bool => self::canReview($record))
                    ->action(function (Withdrawal $record): void {
                        /** @var User $reviewer */
                        $reviewer = auth()->user();
                        app(ReviewWithdrawal::class)->reject($record->id, $reviewer);
                    }),
            ]);
    }

    private static function canReview(Withdrawal $withdrawal): bool
    {
        $reviewer = auth()->user();

        return $withdrawal->status === 'requested'
            && $reviewer instanceof User
            && $reviewer->can('payments.manage')
            && ($reviewer->hasRole('GLOBAL_SUPER_ADMIN') || $reviewer->country_id === $withdrawal->wallet->user->country_id);
    }
}
