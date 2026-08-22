<?php

namespace App\Filament\Resources\Cities\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Schema;

class CityForm
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
                TextInput::make('name')
                    ->required()
                    ->maxLength(120),
                Toggle::make('is_active')
                    ->default(true)
                    ->required(),
            ]);
    }
}
