<?php

namespace App\Filament\Resources\Categories\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

class CategoryForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('country_id')
                    ->relationship('country', 'name')
                    ->searchable()
                    ->preload()
                    ->default(fn (): ?int => auth()->user()?->country_id)
                    ->disabled(fn (): bool => !auth()->user()?->hasRole('GLOBAL_SUPER_ADMIN'))
                    ->dehydrated()
                    ->required(),
                Select::make('parent_id')
                    ->relationship(
                        'parent',
                        'name',
                        modifyQueryUsing: function (Builder $query): Builder {
                            $user = auth()->user();

                            return $user?->hasRole('GLOBAL_SUPER_ADMIN') || !$user?->country_id
                                ? $query
                                : $query->where('country_id', $user->country_id);
                        },
                    )
                    ->searchable()
                    ->preload(),
                TextInput::make('name')
                    ->required()
                    ->maxLength(160),
                TextInput::make('slug')
                    ->required()
                    ->maxLength(180),
                Textarea::make('description')
                    ->columnSpanFull()
                    ->maxLength(2000),
                TextInput::make('sort_order')
                    ->required()
                    ->numeric()
                    ->default(0),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
