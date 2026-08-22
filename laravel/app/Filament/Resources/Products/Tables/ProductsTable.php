<?php

namespace App\Filament\Resources\Products\Tables;

use App\Models\Product;
use Filament\Actions\Action;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class ProductsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('seller.name')
                    ->label('البائع')
                    ->searchable(),
                TextColumn::make('country.name')
                    ->searchable(),
                TextColumn::make('city.name')
                    ->searchable(),
                TextColumn::make('category.name')
                    ->searchable(),
                TextColumn::make('currency.name')
                    ->searchable(),
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('condition')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'approved', 'active' => 'success',
                        'pending_review' => 'warning',
                        'rejected', 'archived' => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('approved_at')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('approved_by')
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
                        'draft' => 'مسودة',
                        'pending_review' => 'بانتظار المراجعة',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        'active' => 'نشط',
                    ]),
            ])
            ->recordActions([
                Action::make('approve')
                    ->label('اعتماد')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record): bool => $record->status === 'pending_review' && (auth()->user()?->can('approve', $record) ?? false))
                    ->action(fn (Product $record) => $record->update([
                        'status' => 'approved',
                        'approved_at' => now(),
                        'approved_by' => auth()->id(),
                    ])),
                Action::make('reject')
                    ->label('رفض')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Product $record): bool => $record->status === 'pending_review' && (auth()->user()?->can('approve', $record) ?? false))
                    ->action(fn (Product $record) => $record->update(['status' => 'rejected'])),
            ]);
    }
}
